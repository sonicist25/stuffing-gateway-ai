<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * GAIL - Gateway AI Logistics Assistant (v2)
 * ==========================================
 * Perubahan besar dari v1:
 * 1. INTENT ROUTING  : Gemini mendeteksi maksud user (buat quote / ubah / tanya / ngobrol)
 *                      sehingga pertanyaan lanjutan tidak dipaksa jadi quote baru.
 * 2. RAG LENGKAP     : Setelah query DB, data rate (validity, remarks, include/exclude,
 *                      transit time, liner) dikirim BALIK ke Gemini untuk menyusun
 *                      jawaban natural. Bukan lagi template hardcode.
 * 3. QUERY DIPERKAYA : Validity difilter (rate expired tidak terambil), remarks &
 *                      metadata rate ikut diambil dan ditampilkan.
 * 4. HISTORY         : Frontend mengirim riwayat chat (bukan cuma draft.header),
 *                      jadi percakapan nyambung.
 *
 * Response format:
 * { status: true, intent: "...", reply: "<jawaban natural AI>", data: <draft|null> }
 */
class Ai_assistant extends RestController {

    // Naikkan setiap deploy — cek via GET /ai_assistant/version utk pastikan file baru yang jalan
    const ENGINE_VERSION = 'v14.2.1-log-fix-2026-08-18';

    private $gemini_api_key;
    private $gemini_url;

    // Tarif standar biaya lokal origin (IDR) — ubah di sini jika tarif berubah
    private $tarif_handling_fcl = 150000; // per container
    private $tarif_doc_fee      = 150000; // per shipment (FCL & LCL)

    // Estimasi default bea masuk + pajak tujuan utk DDP (persen), jika sales tidak menyebut
    private $default_duty_estimate_pct = 15;

    // --- Konfigurasi API partner schedule VIA SIN (docusense-v2.fr8labs.co) ---
    // PENTING: URL ini pola Appwrite (/v1/databases/.../collections/.../documents).
    // Appwrite MEWAJIBKAN header X-Appwrite-Project di SETIAP request, apa pun izin baca
    // koleksinya -- tanpa ini SELALU balas HTTP 401, bukan soal query/parameter.
    // ISI project_id di bawah ini (cek Appwrite console kalian / config app manapun yg
    // sudah pernah connect ke docusense-v2.fr8labs.co). api_key opsional, isi hanya jika
    // collection tidak mengizinkan baca publik dan projectnya minta server-side key.
    private $appwrite_project_id = '691c2e820028aae56fb6'; // <-- WAJIB DIISI, tanpa ini API SIN selalu 401
    private $appwrite_api_key    = ''; // <-- opsional

    // Saklar on/off pencarian schedule via Singapore (Benkel/docusense).
    // AKTIF (TRUE) -- root cause sebelumnya BUKAN appwrite_project_id (sudah terisi
    // sejak awal), melainkan header request kurang lengkap (X-Appwrite-Response-Format
    // & Content-Type). Sudah diperbaiki di schedule_via_sin_api(), lihat juga
    // percakapan/patch terkait Transhipment.php untuk detail root cause-nya.
    private $enable_sin_schedule_lookup = true;

    // --- Konfigurasi DG (Dangerous Goods) ---
    // Dipakai HANYA bila rate khusus DG tidak tersedia di master (fallback dari rate GENCO).
    private $dg_lcl_uplift_pct  = 75;   // % kenaikan O/F LCL di atas rate GENCO (kisaran wajar 50–100%)
    private $dg_fcl_surcharge   = 300;  // USD flat per container di atas all-in GENCO (kisaran 150–500+)
    // Biaya admin DG. Isi angka utk jadi FIXED; biarkan 0 utk tampil AT COST (diisi manual sales).
    private $dg_doc_fee_idr     = 0;    // DG Handling / Approval / Declaration Fee per BL
    private $dg_cfs_per_cbm_usd = 0;    // CFS DG Surcharge per CBM/RT (LCL)

    public function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY, x-api-key");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(0); }

        $this->gemini_api_key = $this->config->item('gemini_api_key');
        $this->gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $this->gemini_api_key;
    }

    // ==========================================================
    // LOW-LEVEL: panggil Gemini (dipakai 2x: extract & compose)
    // ==========================================================
    private function call_gemini($system_instruction, $contents, $force_json = false) {
        $data = [
            "systemInstruction" => ["parts" => [["text" => $system_instruction]]],
            "contents"          => $contents,
        ];
        if ($force_json) {
            $data["generationConfig"] = ["responseMimeType" => "application/json"];
        }

        $ch = curl_init($this->gemini_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        // CATATAN KEAMANAN: sebaiknya aktifkan verifikasi SSL di production.
        // Jika server-mu error SSL, install ca-bundle, jangan matikan verifikasi.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Koneksi cURL gagal: ' . $err];
        }
        curl_close($ch);

        $json = json_decode($response, true);
        if (isset($json['error'])) return ['error' => 'API Error: ' . $json['error']['message']];
        if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) return ['error' => 'Response AI kosong.'];

        return ['text' => $json['candidates'][0]['content']['parts'][0]['text']];
    }

    // Ubah history dari React [{role:'user'|'ai', text:'..'}] jadi format Gemini
    private function build_gemini_contents($history, $latest_prompt) {
        $contents = [];
        if (is_array($history)) {
            // Batasi 10 pesan terakhir agar hemat token
            $history = array_slice($history, -10);
            foreach ($history as $msg) {
                $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'model';
                $text = trim($msg['text'] ?? '');
                if ($text === '') continue;
                $contents[] = ["role" => $role, "parts" => [["text" => $text]]];
            }
        }
        $contents[] = ["role" => "user", "parts" => [["text" => $latest_prompt]]];
        return $contents;
    }

    // ==========================================================
    // LOGGING: simpan setiap putaran chat utk analisa
    // (gagal insert log TIDAK boleh menggagalkan response user)
    // ==========================================================
    private function log_chat($session_id, $prompt, $intent, $ai_data, $reply, $has_draft, $rate_source, $error_msg, $t0) {
        try {
            $user_id = null;
            if (isset($this->session)) {
                $user_id = $this->session->userdata('user_id') ?: $this->session->userdata('username');
            }
            $this->db->insert('ai_chat_logs', [
                'session_id'     => substr($session_id ?: ('anon-' . date('Ymd')), 0, 64),
                'user_id'        => $user_id,
                'region_id'      => is_array($ai_data) ? ($ai_data['region_id'] ?? null) : null,
                'user_prompt'    => $prompt,
                'intent'         => $intent,
                'extracted_json' => is_array($ai_data) ? json_encode($ai_data, JSON_UNESCAPED_UNICODE) : null,
                'destination'    => is_array($ai_data) ? strtoupper(substr($ai_data['destination'] ?? '', 0, 100)) : null,
                'service_type'   => is_array($ai_data) ? substr($ai_data['service_type'] ?? '', 0, 10) : null,
                'term'           => is_array($ai_data) ? substr($ai_data['term'] ?? '', 0, 10) : null,
                'ai_reply'       => $reply,
                'has_draft'      => $has_draft ? 1 : 0,
                'rate_source'    => $rate_source,
                'error_msg'      => $error_msg ? substr($error_msg, 0, 500) : null,
                'exec_ms'        => (int) round((microtime(true) - $t0) * 1000),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'GAIL log_chat gagal: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // STEP 1: EXTRACT + INTENT DETECTION
    // ==========================================================
    private function extract_with_gemini($user_prompt, $history, $context_data) {
        $system = <<<SYS
Kamu adalah parser untuk asisten logistik. Analisa pesan user (dengan mempertimbangkan riwayat chat & draft yang sudah ada) lalu keluarkan JSON:
{
  "intent": "NEW_QUOTE | UPDATE_QUOTE | QUESTION | CHITCHAT | SCHEDULE_ONLY",
  "account_name": "Nama klien tanpa awalan PT/CV/dll, null jika tidak disebut",
  "destination": "Nama pelabuhan/kota tujuan",
  "service_type": "LCL | FCL | AIR",
  "container_size": "20FT | 40FT | 40HC | null (khusus FCL)",
  "qty": 1,
  "uom": "CBM | KGS | CNTR",
  "origin_airport": "khusus AIR EXPORT: kota/bandara ASAL di Indonesia jika disebut (cth Jakarta, Bali/Denpasar), null jika tidak disebut (default Jakarta)",
  "volume_cbm": "khusus AIR: volume dalam CBM jika user menyebut BERAT (KGS) dan volume sekaligus (utk hitung berat volumetrik), null jika tidak disebut",
  "term": "FOB | EXW | CIF | CFR | FCA | DAP | DPU | dll (CNF/C&F = CFR), null jika user tidak menyebut Incoterm dan tidak ada di context",
  "shipment_direction": "EXPORT | IMPORT",
  "job_scope": "ALL_IN jika minta penawaran pengiriman lengkap; CLEARANCE_ONLY jika HANYA minta harga custom clearance / jasa kepabeanan; TRUCKING_ONLY jika HANYA minta harga trucking",
  "origin_port": "pelabuhan/kota ASAL barang (penting utk IMPORT, cth: SHANGHAI), null jika tidak disebut",
  "pickup_requested": "true jika user minta pickup/jemput barang; false jika user bilang antar sendiri / franco gudang / tanpa trucking; null jika TIDAK disebut sama sekali",
  "pickup_address": "alamat pickup jika user menyebutkannya, null jika tidak",
  "pickup_area": "kota/kawasan/kecamatan dari alamat pickup dalam HURUF BESAR (cth: CAKUNG, CIKARANG, JAKARTA UTARA, MARUNDA), null jika tidak diketahui",
  "security_filing": "Tentukan dari NEGARA destinasi: AMS jika USA/China, ENS jika Uni Eropa/UK, AFR jika Jepang, ACI jika Kanada, NONE untuk negara lainnya (cth: Haiphong=Vietnam=NONE, Hamburg=Jerman=ENS, Los Angeles=USA=AMS)",
  "duty_estimate_pct": "khusus DDP: persen estimasi bea masuk+pajak tujuan jika user menyebut angka (cth 'estimasi duty 12%'), null jika tidak disebut",
  "dest_charge_mode": "khusus DDP: ACTUAL_RECEIPT jika user minta biaya tujuan/pajak ditagih terpisah sesuai kwitansi aktual (as per actual receipt); ESTIMATE jika ingin estimasi dimasukkan ke harga; null jika tidak disebut",
  "is_dg": "true jika kargo berbahaya / dangerous goods — indikasi: kata DG, dangerous, hazardous, UN number, MSDS, class 3/8/9 dll, flammable, corrosive, battery lithium, chemical berbahaya. false/null jika tidak ada indikasi",
  "un_number": "nomor UN jika disebut (cth: UN1170), null jika tidak",
  "dg_class": "kelas IMO jika disebut (cth: 3, 8, 2.1, 9), null jika tidak",
  "margin_percentage": null,
  "question_topic": "jika intent=QUESTION, ringkas apa yang ditanyakan user",
  "schedule_via_hint": "khusus SCHEDULE_ONLY: 'SIN' jika user sebut mau lewat/via Singapore, 'HKG' jika via Hong Kong, null jika tidak disebut (biar sistem cari sendiri direct/via mana yg ada)"
}
Aturan intent:
- NEW_QUOTE   : user minta penawaran baru / menyebut rute+layanan baru.
- UPDATE_QUOTE: user mengubah draft yang sudah ada (ganti qty, term, tambah margin, ganti tujuan, dll). Pertahankan field lain dari konteks draft.
- QUESTION    : user bertanya tentang draft/rate yang sudah ditampilkan (validity, kenapa harga segitu, apa yang termasuk, transit time, dll) TANPA minta perubahan.
- SCHEDULE_ONLY: user HANYA menanyakan jadwal kapal/kapan berangkat/kapan sampai/vessel apa (kata kunci: "schedule", "jadwal", "kapan berangkat", "kapan sampai", "vessel apa", "ETD/ETA"), TANPA sama sekali minta harga/quotation. Kalau user minta jadwal SEKALIGUS harga dalam 1 kalimat, itu tetap NEW_QUOTE (bukan SCHEDULE_ONLY) — SCHEDULE_ONLY hanya utk permintaan jadwal yang berdiri sendiri.
- CHITCHAT    : sapaan / basa-basi / di luar topik quotation.
Aturan field:
- pickup_requested: JANGAN isi false hanya karena tidak disebut — gunakan null. false HANYA jika user eksplisit menolak (antar sendiri/franco/tanpa pickup).
- margin_percentage: HANYA isi jika user menyebut angka margin. Jika user bilang "tanpa margin"/"sesuai rate"/"net", isi 0. Jika tidak disebut sama sekali, biarkan null (JANGAN isi 0).
- term / Incoterm: JANGAN PERNAH menebak atau memberi default. Jika user tidak menyebut Incoterm dan tidak ada Incoterm di context, isi null. JANGAN menganggap FOB hanya karena shipment EXPORT. JANGAN menganggap CIF/CFR hanya karena freight disebut. Jika ada term di context sebelumnya dan user tidak mengubahnya, pertahankan term tersebut.
- Bahasa pesan TIDAK memengaruhi nilai field. Parser tetap keluarkan JSON sesuai schema di atas.
- Jika ada konteks draft/pending sebelumnya, isi field yang tidak disebut user dengan nilai dari konteks (JANGAN null-kan kembali).
Keluarkan HANYA JSON.
SYS;

        $prompt = $user_prompt;
        if (!empty($context_data)) {
            $prompt = "Konteks draft saat ini:\n" . json_encode($context_data, JSON_UNESCAPED_UNICODE) . "\n\nPesan baru user:\n" . $user_prompt;
        }

        $res = $this->call_gemini($system, $this->build_gemini_contents($history, $prompt), true);
        if (isset($res['error'])) return $res;

        $parsed = json_decode(trim($res['text']), true);
        if (json_last_error() !== JSON_ERROR_NONE) return ['error' => 'Gagal membaca JSON dari AI.'];
        return $parsed;
    }

    // ==========================================================
    // STEP 3: COMPOSE JAWABAN NATURAL (inti "kecerdasan" baru)
    // ==========================================================
    private function compose_reply($user_prompt, $history, $draft, $rate_meta, $intent) {
        $reply_lang = $this->preferred_reply_language($user_prompt, $history);
        $language_instruction = $reply_lang === 'EN'
            ? 'OUTPUT LANGUAGE: ENGLISH ONLY. Do not answer in Indonesian.'
            : 'OUTPUT LANGUAGE: BAHASA INDONESIA. Jangan menjawab dalam bahasa Inggris kecuali istilah logistik yang lazim.';

        $system = <<<SYS
{$language_instruction}

Kamu adalah GAIL, asisten AI logistik dari Gateway Container Line. Nada: santai-profesional, ringkas, jelas, boleh emoji secukupnya.

ATURAN BAHASA & GAYA (WAJIB):
- Jawab dalam bahasa yang dipakai user pada PESAN TERBARU. Jika pesan terbaru berbahasa Inggris, jawab dalam bahasa Inggris. Jika berbahasa Indonesia, jawab dalam bahasa Indonesia.
- Jika pesan terbaru sangat pendek/ambigu seperti "FOB", "CIF", "yes", "ok", atau hanya angka, gunakan bahasa dominan dari percakapan user sebelumnya.
- JANGAN selalu membuka jawaban dengan sapaan seperti "Halo", "Hai", "Hi", "Hello", "Selamat pagi/siang/sore". Untuk pertanyaan, update, klarifikasi, dan follow-up, langsung jawab ke inti.
- Sapaan boleh dipakai HANYA jika pesan terbaru user memang berupa sapaan/basa-basi atau ini benar-benar interaksi pembuka.
- JANGAN memperkenalkan diri berulang kali sebagai GAIL pada setiap jawaban.

Tugasmu: jawab pesan user berdasarkan DATA yang diberikan sistem. ATURAN KERAS:
1. JANGAN PERNAH mengarang harga, validity, atau ketentuan. Hanya gunakan angka & info dari blok DATA.
2. Jika sebuah komponen bertanda QUOTE_REQUIRED / harga 0, katakan jujur bahwa rate perlu dimintakan ke tim pricing.
2b. Arti pricing_basis: FIXED=harga pasti, AT_COST=sesuai biaya aktual/kwitansi, PER_RECEIPT=as per receipt, IF_ANY=hanya jika berlaku, QUOTE_REQUIRED=rate belum ada, COLLECT=freight dibayar pihak lain (Freight Collect, JANGAN sebut sebagai biaya customer), EXCLUDED=di luar harga & dibayar buyer langsung di tujuan, ESTIMATE=estimasi yang akan disesuaikan dgn kwitansi aktual, INFO=baris informasi saja bukan tagihan. Item non-FIXED JANGAN disebut "Rp 0" — sebut basisnya (at cost / as per receipt / excluded / estimated / if any).
2f. Jika ada metadata source=INCOTERM_NOTE: klausul tsb adalah format resmi quotation — WAJIB sampaikan intinya ke sales (inclusions/exclusions/estimasi & klausul penyesuaian) agar dicantumkan saat penawaran dikirim ke customer.
2g. Jika kargo DG (metadata DG_CARGO/DG_SURCHARGE/DG_WARNING): WAJIB sebutkan bahwa MSDS harus dikirim sebelum booking, acceptance & final rate subject to carrier approval, dan jika surcharge/uplift bersifat ESTIMASI katakan dengan jelas. Jika ada DG_WARNING (agen tidak menerima DG), sampaikan sebagai peringatan utama.
2c. Jelaskan singkat konsekuensi Incoterm bila relevan, tetapi SELALU lihat shipment_direction dan pihak yang menjadi customer. Jangan memakai generalisasi yang bisa salah seperti "FOB selalu freight collect" tanpa konteks export/import. Jika ada minimum charge (mis. kiriman dihitung minimum 3 CBM), sebutkan.
2d. Jika header.job_scope = CLEARANCE_ONLY atau TRUCKING_ONLY: ini penawaran jasa tunggal — JANGAN membahas ocean freight, incoterm, atau biaya lain di luar lingkup yang diminta.
2e. Jika ada metadata source=MARGIN (margin default): WAJIB beri tahu sales secara jelas bahwa margin 20% adalah DEFAULT karena tidak disebutkan, dan bisa diubah dengan menyebut angka baru.
2h. Jika draft.schedules ada isinya: jadwal hanya informasi pembantu sales. Ringkas hanya jadwal yang berguna bagi user. JANGAN PERNAH menampilkan error teknis, nama endpoint/API internal, pesan debugging partner, format query, atau teks seperti "Detail teknis schedule" kepada user. Jika jadwal tidak tersedia/gagal dicek, cukup katakan jadwal belum dapat dikonfirmasi dan sarankan konfirmasi ke tim operasional.
Jika draft.schedules null/kosong, JANGAN membahas jadwal kecuali intent memang SCHEDULE_ONLY.
3. Sebutkan info penting yang relevan: masa berlaku rate (validity), apa yang SUDAH termasuk (include) dan BELUM termasuk (exclude), transit time, liner, dan catatan (remarks) jika ada.
4. Jika rate yang ditemukan sudah/akan segera expired, beri peringatan.
5. Jika intent=QUESTION, jawab pertanyaannya langsung dari DATA tanpa menyuruh user membuat quote ulang.
6. Jawab ringkas (maksimal ~150 kata), format mudah dibaca. Detail tabel harga sudah tampil di panel kanan, jadi jangan mengulang semua angka — cukup highlight & ringkasan.
SYS;

        $data_block = "=== DATA SISTEM ===\n";
        $data_block .= "Intent terdeteksi: $intent\n";
        if ($draft)     $data_block .= "Draft quotation:\n" . json_encode($draft, JSON_UNESCAPED_UNICODE) . "\n";
        if ($rate_meta) $data_block .= "Metadata rate (validity/remarks/dll):\n" . json_encode($rate_meta, JSON_UNESCAPED_UNICODE) . "\n";
        $data_block .= "===================\n\nPesan user: " . $user_prompt;

        $res = $this->call_gemini($system, $this->build_gemini_contents($history, $data_block), false);
        if (isset($res['error'])) {
            $lang = $this->preferred_reply_language($user_prompt, $history);
            return $lang === 'EN'
                ? "The draft is ready; please review the panel on the right. (Note: AI summary generation failed: " . $res['error'] . ")"
                : "Draft sudah saya siapkan, silakan cek panel kanan. (Catatan: gagal menyusun ringkasan AI: " . $res['error'] . ")";
        }
        return $this->clean_reply_opening(trim($res['text']), $user_prompt);
    }

    // ==========================================================
    // GET /ai_assistant/version — cek cepat versi yang ter-deploy.
    // Buka di browser; jika versi tidak berubah setelah upload,
    // berarti OPcache masih menyajikan file lama → restart PHP/Apache.
    // ==========================================================
    public function version_get() {
        $this->response([
            'engine_version' => self::ENGINE_VERSION,
            'php_file_mtime' => date('Y-m-d H:i:s', filemtime(__FILE__)),
            'opcache'        => function_exists('opcache_get_status') && @opcache_get_status(false) ? 'ON' : 'OFF/unknown',
        ], 200);
    }

    // ==========================================================
    // SCHEDULE LOOKUP — dipanggil setelah harga ketemu, utk bantu sales
    // cari jadwal kapal. 4 sumber:
    //  1) tbl_schedules_direct       -> EXPORT direct Jakarta ke kota tsb
    //  2) tbl_schedules_direct_import-> IMPORT direct dari kota asal ke Jakarta
    //  3) tbl_schedules_via          -> transhipment umum (1 baris = 1 rute lengkap
    //                                   dgn connecting vessel, bukan via SIN/HKG khusus)
    //  4) API eksternal (HANYA dipakai bila harga ditemukan via TS_RATE dgn
    //     ts_type VIA SIN / VIA HKG): leg 1 tetap dari tabel lokal (Jakarta->hub),
    //     leg 2 (hub->tujuan akhir) dari API partner (Benkel via docusense utk SIN,
    //     Charterlink utk HKG) krn itu bukan armada/rute milik kita sendiri.
    // ==========================================================

    private function schedule_direct($city_keyword, $direction = 'EXPORT', $limit = 5) {
        $today = date('Y-m-d');
        if ($direction === 'IMPORT') {
            $rows = $this->db->where('isDeleted', 0)
                ->where('etd >=', $today)
                ->like('city_id', $city_keyword)
                ->order_by('etd', 'ASC')->limit($limit)
                ->get('tbl_schedules_direct_import')->result();
            $out = [];
            foreach ($rows as $r) {
                $out[] = ['vessel' => $r->vessel, 'voyage' => $r->voy, 'etd' => $r->etd,
                          'eta_jkt' => $r->eta_jkt, 'cut_off' => $r->cut_off, 'city' => $r->city_id];
            }
            return $out;
        }
        $rows = $this->db->where('isDeleted', 0)
            ->where('etd_jkt >=', $today)
            ->like('city_id', $city_keyword)
            ->order_by('etd_jkt', 'ASC')->limit($limit)
            ->get('tbl_schedules_direct')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['vessel' => $r->vessel, 'voyage' => $r->voy, 'etd_jkt' => $r->etd_jkt,
                      'eta' => $r->eta, 'stf_cls' => $r->stf_cls, 'city' => $r->city_id, 'carrier' => $r->carrier];
        }
        return $out;
    }

    private function schedule_via($pod_keyword, $limit = 5) {
        $today = date('Y-m-d');
        $rows = $this->db->where('isDeleted', 0)
            ->where('etd_jkt >=', $today)
            ->group_start()
                ->like('pod', $pod_keyword)
                ->or_like('eta_city_id', $pod_keyword)
                ->or_like('final_destination', $pod_keyword)
            ->group_end()
            ->order_by('etd_jkt', 'ASC')->limit($limit)
            ->get('tbl_schedules_via')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'vessel' => $r->vessel, 'voyage' => $r->voy_vessel, 'stf_cls' => $r->stf_cls,
                'etd_jkt' => $r->etd_jkt, 'connecting_vessel' => $r->connecting, 'voyage_connecting' => $r->voy_con,
                'etd_connecting' => $r->etd_con, 'transit_port' => $r->etd_city_con_id ?: $r->transit_port,
                'eta' => $r->eta, 'eta_city' => $r->eta_city_id, 'pod' => $r->pod
            ];
        }
        return $out;
    }

    // Resolusi nama tempat -> baris tableName yg PALING BENAR. PENTING: banyak nama
    // kota di tableName AMBIGU -- cth "MANZANILLO" ada di Mexico, Cuba, Panama,
    // Dominican Republic, DAN El Salvador sekaligus (5 baris utk 1 nama)! Tanpa
    // ORDER BY, MySQL bisa mengembalikan baris INACTIVE/negara yg salah scr acak,
    // menyebabkan pod_code/negara yg diresolve salah total (bug nyata yg ditemukan).
    // Strategi: (1) exact match dulu (case-insensitive, data tableName konsisten
    // uppercase), utamakan is_active='TRUE'; (2) fallback substring LIKE kalau exact
    // tdk ketemu, jg utamakan is_active='TRUE'.
    private function resolve_location_row($place_name) {
        $place_name = strtoupper(trim((string) $place_name));
        if ($place_name === '') return null;
        $row = $this->db->where('name', $place_name)
            ->order_by('is_active', 'DESC')
            ->limit(1)->get('tableName')->row();
        if ($row) return $row;
        return $this->db->like('name', $place_name)
            ->order_by('is_active', 'DESC')
            ->limit(1)->get('tableName')->row();
    }

    // Resolusi nama tempat -> kode UN/LOCODE 5-huruf via tableName.code
    // (dipakai utk pol_code/pod_code API docusense, cth SINGAPORE->SGSIN, HAMBURG->DEHAM)
    private function resolve_unlocode($place_name) {
        $row = $this->resolve_location_row($place_name);
        return $row ? strtoupper(trim($row->code)) : null;
    }

    // Peta kode negara ISO 3166-1 alpha-2 -> nama (dipakai bentuk "KOTA / NEGARA"
    // persis format yg dibutuhkan Charterlink, cth "MANZANILLO / MEXICO"). Tidak
    // exhaustive 195 negara -- cukup negara2 yg lazim jadi destinasi freight.
    // Kalau destinasi negaranya tdk ada di daftar ini, fallback: kirim nama kota
    // polos saja (bisa gagal match, tapi tdk fatal -- leg1 tetap tampil).
    private $iso_country_map = [
        'AD'=>'ANDORRA','AE'=>'UNITED ARAB EMIRATES','AF'=>'AFGHANISTAN','AR'=>'ARGENTINA',
        'AT'=>'AUSTRIA','AU'=>'AUSTRALIA','BD'=>'BANGLADESH','BE'=>'BELGIUM','BG'=>'BULGARIA',
        'BH'=>'BAHRAIN','BR'=>'BRAZIL','CA'=>'CANADA','CH'=>'SWITZERLAND','CL'=>'CHILE',
        'CN'=>'CHINA','CO'=>'COLOMBIA','CU'=>'CUBA','CZ'=>'CZECH REPUBLIC','DE'=>'GERMANY',
        'DK'=>'DENMARK','DO'=>'DOMINICAN REPUBLIC','EC'=>'ECUADOR','EG'=>'EGYPT','ES'=>'SPAIN',
        'FI'=>'FINLAND','FR'=>'FRANCE','GB'=>'UNITED KINGDOM','GR'=>'GREECE','HK'=>'HONG KONG',
        'HU'=>'HUNGARY','ID'=>'INDONESIA','IE'=>'IRELAND','IL'=>'ISRAEL','IN'=>'INDIA',
        'IQ'=>'IRAQ','IR'=>'IRAN','IT'=>'ITALY','JO'=>'JORDAN','JP'=>'JAPAN','KE'=>'KENYA',
        'KR'=>'SOUTH KOREA','KW'=>'KUWAIT','LB'=>'LEBANON','LK'=>'SRI LANKA','MA'=>'MOROCCO',
        'MM'=>'MYANMAR','MX'=>'MEXICO','MY'=>'MALAYSIA','NG'=>'NIGERIA','NL'=>'NETHERLANDS',
        'NO'=>'NORWAY','NZ'=>'NEW ZEALAND','OM'=>'OMAN','PA'=>'PANAMA','PE'=>'PERU',
        'PH'=>'PHILIPPINES','PK'=>'PAKISTAN','PL'=>'POLAND','PT'=>'PORTUGAL','QA'=>'QATAR',
        'RO'=>'ROMANIA','RU'=>'RUSSIA','SA'=>'SAUDI ARABIA','SE'=>'SWEDEN','SG'=>'SINGAPORE',
        'SV'=>'EL SALVADOR','TH'=>'THAILAND','TR'=>'TURKEY','TW'=>'TAIWAN','TZ'=>'TANZANIA',
        'UA'=>'UKRAINE','US'=>'UNITED STATES','UY'=>'URUGUAY','VE'=>'VENEZUELA',
        'VN'=>'VIETNAM','ZA'=>'SOUTH AFRICA',
    ];

    // Resolusi nama tempat -> nama negara (via tableName.country_id + peta ISO di atas).
    // Return null kalau tempat/negaranya tdk ketemu -- JANGAN menebak.
    private function resolve_country_name($place_name) {
        $row = $this->resolve_location_row($place_name);
        if (!$row || empty($row->country_id)) return null;
        $code = strtoupper(trim($row->country_id));
        return $this->iso_country_map[$code] ?? null;
    }

    // Leg lanjutan VIA SINGAPORE — API partner Benkel (docusense/Appwrite-style)
    private function schedule_via_sin_api($pod_code, $limit = 500) {
        if (empty($pod_code)) return ['ok' => false, 'error' => 'pod_code tidak diketahui', 'schedules' => []];
        if (empty($this->appwrite_project_id)) {
            // Gagal cepat dgn pesan yg JELAS penyebabnya, drpd nunggu 401 generik dari Appwrite.
            return ['ok' => false, 'error' => 'Konfigurasi API belum lengkap (Appwrite Project ID belum diisi di server) — hubungi admin sistem.', 'schedules' => []];
        }
        $base = 'https://docusense-v2.fr8labs.co/v1/databases/sailing-schedule-ecosystem-db/collections/nvo_schedules_benkel/documents';
        $query = http_build_query([
            'queries[0]' => 'equal("tenant_id", ["benkel"])',
            'queries[1]' => 'equal("pol_code", ["SGSIN"])',
            'queries[2]' => 'equal("pod_code", ["' . $pod_code . '"])',
            'queries[3]' => 'limit(' . (int) $limit . ')',
        ]);
        // PENTING: selain X-Appwrite-Project, server Appwrite ini juga butuh
        // X-Appwrite-Response-Format & Content-Type persis seperti request browser --
        // tanpa ini query yg formatnya sudah benar pun tetap bisa ditolak.
        $headers = [
            'X-Appwrite-Project: ' . $this->appwrite_project_id,
            'X-Appwrite-Response-Format: 1.4.0',
            'Content-Type: application/json',
        ];
        if (!empty($this->appwrite_api_key)) $headers[] = 'X-Appwrite-Key: ' . $this->appwrite_api_key;
        $resp = $this->http_get_json($base . '?' . $query, 12, $headers);
        if (!$resp['ok']) return ['ok' => false, 'error' => $resp['error'], 'schedules' => []];

        $today = date('Y-m-d');
        $docs = $resp['json']['documents'] ?? [];
        $out = [];
        foreach ($docs as $d) {
            $etd_date = substr((string) ($d['etd'] ?? ''), 0, 10);
            if ($etd_date === '' || $etd_date < $today) continue; // hanya yg valid (blm lewat)
            $out[] = [
                'vessel' => $d['vessel'] ?? null, 'voyage' => $d['voyage_no'] ?? null,
                'carrier' => $d['carrier_code'] ?? null,
                'pol' => $d['pol'] ?? null, 'pod' => $d['pod'] ?? null,
                'etd' => $etd_date, 'eta_pol' => substr((string)($d['eta_pol'] ?? ''), 0, 10),
                'eta_dest' => substr((string)($d['eta_dest'] ?? ''), 0, 10),
                'cutoff' => substr((string)($d['cutoff'] ?? ''), 0, 10),
                'dg_cutoff' => substr((string)($d['dg_cutoff'] ?? ''), 0, 10),
                'transit_days' => $d['transit_days'] ?? null,
            ];
        }
        usort($out, function ($a, $b) { return strcmp($a['etd'], $b['etd']); });
        return ['ok' => true, 'error' => null, 'schedules' => $out];
    }

    // Leg lanjutan VIA HONG KONG — API partner Charterlink.
    // PENTING: API ini MEWAJIBKAN parameter destination diisi PERSIS format
    // "KOTA / NEGARA" (cth "MANZANILLO / MEXICO") -- destination polos ("MANZANILLO"
    // saja) TIDAK match, dan memanggil tanpa parameter destination sama sekali JUGA
    // tidak membantu (bukan "ambil semua", API-nya butuh destination). Strategi:
    // coba nama polos dulu (siapa tau ada rute yg formatnya memang cuma 1 kata),
    // kalau kosong coba lagi dgn "KOTA / NEGARA" hasil resolve_country_name().
    private function schedule_via_hkg_api($dest_name, $limit = 10) {
        $base = 'https://www.charterlinkgroup.com/Schedule/GetList';

        $fetch = function ($destination_param) use ($base, $limit) {
            $resp = $this->http_get_json($base . '?' . http_build_query(['origin' => 'HONG KONG', 'destination' => $destination_param]));
            if (!$resp['ok']) return ['ok' => false, 'error' => $resp['error'], 'schedules' => []];
            $today = date('Y-m-d');
            $rows = $resp['json']['data'] ?? [];
            $out = [];
            foreach ($rows as $r) {
                $etd_date = substr((string) ($r['Etd'] ?? ''), 0, 10);
                if ($etd_date === '' || $etd_date < $today) continue; // hanya yg valid (blm lewat)
                $out[] = [
                    'vessel' => $r['Vessel'] ?? null, 'voyage' => $r['Voyage'] ?? null,
                    'pol' => $r['pol'] ?? null, 'pod' => $r['pod'] ?? null,
                    'etd' => $etd_date, 'eta' => substr((string)($r['Eta'] ?? ''), 0, 10),
                    'cfs_closing' => substr((string)($r['CFS_CLOSING_DATE'] ?? ''), 0, 10),
                    'si_cutoff' => substr((string)($r['SI_CUTOFF_DATE'] ?? ''), 0, 10),
                    'is_tba' => stripos((string) ($r['Vessel'] ?? ''), 'TBA') !== false,
                ];
                if (count($out) >= $limit) break;
            }
            usort($out, function ($a, $b) { return strcmp($a['etd'], $b['etd']); });
            return ['ok' => true, 'error' => null, 'schedules' => $out];
        };

        // Percobaan 1: nama kota polos
        $attempt1 = $fetch($dest_name);
        if ($attempt1['ok'] && !empty($attempt1['schedules'])) return $attempt1;

        // Percobaan 2: format "KOTA / NEGARA" -- HANYA kalau negara berhasil di-resolve
        $country = $this->resolve_country_name($dest_name);
        if ($country !== null && strtoupper($country) !== strtoupper($dest_name)) {
            $tried_destination = $dest_name . ' / ' . $country;
            $attempt2 = $fetch($tried_destination);
            if ($attempt2['ok'] && !empty($attempt2['schedules'])) return $attempt2;
            if ($attempt2['ok']) {
                // API merespons normal TAPI 0 hasil -- kasih tau PERSIS format yg sudah
                // dicoba, supaya kalau msh salah, langsung ketauan bukan cuma "gagal".
                return ['ok' => false, 'schedules' => [],
                    'error' => "Sudah dicoba destination=\"$tried_destination\" ke Charterlink, API merespons normal tapi 0 hasil. " .
                               "Kemungkinan format nama kota/negaranya sedikit beda dari yg dipakai Charterlink."];
            }
            // Attempt2 gagal koneksi/HTTP -- tambahkan konteks apa yg dicoba
            $attempt2['error'] = "Percobaan format \"$tried_destination\": " . $attempt2['error'];
            return $attempt2;
        }

        // Negara tdk berhasil diresolve dari tableName -- ini kemungkinan besar akar masalahnya
        $attempt1['error'] = $attempt1['ok']
            ? "Destination=\"$dest_name\" ke Charterlink 0 hasil. Percobaan format \"KOTA / NEGARA\" DILEWATI krn negara tujuan " .
              "tidak berhasil diresolve dari master lokasi (tableName) -- cek apakah \"$dest_name\" ada di tableName dgn kolom country_id terisi."
            : $attempt1['error'];
        $attempt1['ok'] = false;

        return $attempt1;
    }

    // Helper generik GET + decode JSON, dgn error handling supaya API partner
    // yg down/lambat TIDAK menggagalkan seluruh response quotation.
    // $extra_headers: array string header lengkap dari caller, cth ['X-Appwrite-Project: xxx', ...]
    private function http_get_json($url, $timeout = 12, $extra_headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // PENTING: kalau caller sudah kirim $extra_headers (mis. utk Appwrite), pakai PERSIS itu --
        // JANGAN dicampur 'Accept: application/json' lagi. Appwrite strict soal header yg diterima;
        // histori fix di Transhipment.php membuktikan harus PERSIS 3 header (Project, Response-Format,
        // Content-Type) tanpa tambahan apa pun, kalau tidak balik lagi jadi "Invalid query: Syntax error".
        $headers = !empty($extra_headers) ? $extra_headers : ['Accept: application/json'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'Gagal menghubungi API schedule: ' . $err, 'json' => null];
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code < 200 || $http_code >= 300) {
            // Coba baca pesan error dari body (Appwrite/API lain biasanya kirim {"message": "..."}) --
            // jauh lebih berguna drpd cuma kode HTTP polos utk debugging.
            $err_body = json_decode($body, true);
            $err_msg = is_array($err_body) && !empty($err_body['message']) ? $err_body['message'] : null;
            return ['ok' => false, 'error' => "API schedule merespons HTTP $http_code" . ($err_msg ? ": $err_msg" : ''), 'json' => null];
        }
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'error' => 'Respons API schedule bukan JSON valid', 'json' => null];
        }
        return ['ok' => true, 'error' => null, 'json' => $json];
    }

    // Orkestrasi utama: pilih sumber schedule yg tepat berdasarkan ts_type (bila
    // harga ditemukan via TS_RATE) atau fallback ke direct/via lokal utk kasus lain.
    private function find_schedules($direction, $service, $dest_name, $ts_type_matched) {
        if (!in_array($service, ['LCL', 'FCL'])) {
            return null; // schedule khusus sea freight; AIR tdk pakai mekanisme ini
        }

        if ($ts_type_matched === 'VIA SIN') {
            $leg1 = $this->schedule_direct('SINGAPORE', $direction, 5);
            if (!$this->enable_sin_schedule_lookup) {
                return [
                    'routing' => 'VIA SIN',
                    'leg1_label' => 'Jakarta → Singapore (armada sendiri)',
                    'leg1' => $leg1,
                    'leg2_label' => 'Singapore → ' . $dest_name . ' (partner: Benkel)',
                    'leg2' => [],
                    'leg2_error' => 'Pencarian jadwal partner Singapore (Benkel) sedang dinonaktifkan sementara — konfirmasi manual ke tim operasional.',
                    'pod_code_used' => null,
                ];
            }
            $pod_code = $this->resolve_unlocode($dest_name);
            $leg2_resp = $this->schedule_via_sin_api($pod_code);
            return [
                'routing' => 'VIA SIN',
                'leg1_label' => 'Jakarta → Singapore (armada sendiri)',
                'leg1' => $leg1,
                'leg2_label' => 'Singapore → ' . $dest_name . ' (partner: Benkel)',
                'leg2' => $leg2_resp['schedules'],
                'leg2_error' => $leg2_resp['ok'] ? null : $leg2_resp['error'],
                'pod_code_used' => $pod_code,
            ];
        }

        if ($ts_type_matched === 'VIA HKG') {
            $leg1 = $this->schedule_direct('HONG KONG', $direction, 5);
            $leg2_resp = $this->schedule_via_hkg_api($dest_name);
            return [
                'routing' => 'VIA HKG',
                'leg1_label' => 'Jakarta → Hong Kong (armada sendiri)',
                'leg1' => $leg1,
                'leg2_label' => 'Hong Kong → ' . $dest_name . ' (partner: Charterlink)',
                'leg2' => $leg2_resp['schedules'],
                'leg2_error' => $leg2_resp['ok'] ? null : $leg2_resp['error'],
            ];
        }

        // Bukan via TS_RATE (rate_meta TS_RATE tdk match / master/history lain) ->
        // coba jadwal direct & via umum langsung ke tujuan.
        $direct = $this->schedule_direct($dest_name, $direction, 5);
        $via = $this->schedule_via($dest_name, 5);
        if (!empty($direct) || !empty($via)) {
            return ['routing' => 'DIRECT_OR_VIA', 'direct' => $direct, 'via' => $via];
        }

        // Tidak ada jadwal direct/via di tabel sendiri -> jangan langsung menyerah.
        // Kemungkinan besar tujuan ini memang lazimnya lewat T/S -- cek hub yg AKTIF
        // sblm bilang "tidak ada". PENTING: leg1 (Jakarta->hub) dihitung DULUAN &
        // independen dari leg2 -- ini data kita sendiri, harus tetap tampil walau
        // partner luar gagal/kosong/dinonaktifkan.
        $hkg_leg1 = $this->schedule_direct('HONG KONG', $direction, 3);
        $hkg_resp = $this->schedule_via_hkg_api($dest_name);
        $has_hkg_leg2 = $hkg_resp['ok'] && !empty($hkg_resp['schedules']);
        $show_hkg = !empty($hkg_leg1) || $has_hkg_leg2;

        // SIN (Benkel) dilewati SEPENUHNYA selama dinonaktifkan -- hemat 1 network
        // call yg pasti gagal (blm ada Appwrite Project ID).
        if ($this->enable_sin_schedule_lookup) {
            $sin_leg1 = $this->schedule_direct('SINGAPORE', $direction, 3);
            $pod_code = $this->resolve_unlocode($dest_name);
            $sin_resp = $this->schedule_via_sin_api($pod_code);
            $has_sin_leg2 = $sin_resp['ok'] && !empty($sin_resp['schedules']);
            $show_sin = !empty($sin_leg1) || $has_sin_leg2;
        } else {
            $sin_leg1 = []; $sin_resp = ['ok' => true, 'error' => null, 'schedules' => []];
            $show_sin = false;
        }

        // Opsi ditampilkan selama SALAH SATU leg (punya sendiri ATAU partner) ada isinya --
        // BUKAN cuma kalau leg2 berhasil. Ini yg memperbaiki bug "leg1 tdk muncul".

        if (!$show_sin && !$show_hkg) {
            // Benar2 tidak ketemu di mana pun -- checked-list JUJUR: sebutkan Singapore
            // hanya kalau memang dicek (bukan berpura-pura sudah dicek padahal di-skip).
            $checked = ['Direct dari Jakarta', 'Via transhipment umum', 'Via Hong Kong (Charterlink)'];
            if ($this->enable_sin_schedule_lookup) $checked[] = 'Via Singapore (Benkel)';
            return [
                'routing' => 'NOT_FOUND_CHECKED_HUBS',
                'checked' => $checked,
                'sin_error' => $this->enable_sin_schedule_lookup ? ($sin_resp['ok'] ? null : $sin_resp['error']) : null,
                'hkg_error' => $hkg_resp['ok'] ? null : $hkg_resp['error'],
            ];
        }

        // Salah satu/kedua hub punya info (leg1 dan/atau leg2) -> tampilkan sbg opsi
        $options = [];
        if ($show_sin) {
            $options[] = [
                'via' => 'SIN', 'label' => 'Via Singapore (partner: Benkel)',
                'leg1_label' => 'Jakarta → Singapore (armada sendiri)',
                'leg1' => $sin_leg1,
                'leg2_label' => 'Singapore → ' . $dest_name,
                'leg2' => $sin_resp['schedules'],
                'leg2_error' => $sin_resp['ok'] ? null : $sin_resp['error'],
            ];
        }
        if ($show_hkg) {
            $options[] = [
                'via' => 'HKG', 'label' => 'Via Hong Kong (partner: Charterlink)',
                'leg1_label' => 'Jakarta → Hong Kong (armada sendiri)',
                'leg1' => $hkg_leg1,
                'leg2_label' => 'Hong Kong → ' . $dest_name,
                'leg2' => $hkg_resp['schedules'],
                'leg2_error' => $hkg_resp['ok'] ? null : $hkg_resp['error'],
            ];
        }
        return ['routing' => 'HUB_FALLBACK', 'options' => $options];
    }

    // ==========================================================
    // LANGUAGE HELPER
    // ==========================================================
    private function detect_language_from_text($text) {
        $text = strtolower(trim((string)$text));
        if ($text === '') return null;

        // Score pesan itu sendiri. Jangan campur semua history sekaligus karena
        // pesan terbaru English setelah percakapan Indonesia harus tetap dijawab English.
        $id_pattern = '/\b(saya|aku|kami|kita|anda|kamu|tolong|mohon|cek|buat|buatkan|berapa|apakah|bisa|tidak|ngga|belum|sudah|minta|harga|jadwal|kirim|pengiriman|dari|ke|yang|dan|atau|dengan|untuk|ini|itu|jadi|kalau|kalo|mau|perlu|tujuan|asal|terima\s+kasih)\b/u';
        $en_pattern = '/\b(hello|hi|hey|please|my|boss|ask|asked|me|we|our|you|your|need|want|get|fee|price|cost|from|to|for|the|and|or|with|can|could|would|what|how|is|are|thanks|thank\s+you)\b/u';

        preg_match_all($id_pattern, $text, $id_hits);
        preg_match_all($en_pattern, $text, $en_hits);
        $id_score = count($id_hits[0] ?? []);
        $en_score = count($en_hits[0] ?? []);

        if ($en_score > $id_score && $en_score >= 1) return 'EN';
        if ($id_score > $en_score && $id_score >= 1) return 'ID';
        return null;
    }

    private function preferred_reply_language($prompt, $history = []) {
        // 1) Pesan terbaru menang jika bahasanya cukup jelas.
        $latest = $this->detect_language_from_text($prompt);
        if ($latest !== null) return $latest;

        // 2) Pesan pendek/ambigu ("Tanjung Priok", "FOB", "yes", angka)
        //    mengikuti bahasa user sebelumnya, bukan bahasa system prompt.
        if (is_array($history)) {
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $msg = $history[$i] ?? [];
                if (($msg['role'] ?? '') !== 'user') continue;
                $lang = $this->detect_language_from_text($msg['text'] ?? '');
                if ($lang !== null) return $lang;
            }
        }

        return 'ID';
    }

    // Hilangkan sapaan & perkenalan diri repetitif bila user bukan sekadar menyapa.
    private function clean_reply_opening($reply, $user_prompt) {
        $reply = trim((string)$reply);
        if ($reply === '') return $reply;

        $p = strtolower(trim((string)$user_prompt));
        $is_greeting_only = (bool) preg_match(
            '/^(halo|hai|hi|hello|hey|selamat\s+(pagi|siang|sore|malam))(\s+gail)?[!.,?\s]*$/iu',
            $p
        );
        if ($is_greeting_only) return $reply;

        // Support plain maupun markdown: "Halo!", "**Hello!**", dst.
        $reply = preg_replace(
            '/^\s*(?:\*\*|__)?\s*(?:(?:halo|hai|hi|hello|hey)(?:\s+(?:pak|bu|kak|sir|madam|ma[’\']?am|there|team))?|selamat\s+(?:pagi|siang|sore|malam))\s*[!.,:;\-–—]*\s*(?:\*\*|__)?\s*/iu',
            '',
            $reply,
            1
        );

        // Setelah greeting dibuang, jangan biarkan perkenalan diri berulang.
        $reply = preg_replace(
            '/^\s*(?:(?:saya\s+)?GAIL(?:\s+dari\s+Gateway Container Line)?|I(?:\'|’)m\s+GAIL(?:\s+from\s+Gateway Container Line)?|I\s+am\s+GAIL(?:\s+from\s+Gateway Container Line)?)\s*[👋✨!.,:;\-–—]*\s*/iu',
            '',
            $reply,
            1
        );

        return trim((string)$reply);
    }

    // ==========================================================
    // MAIN ENDPOINT
    // ==========================================================
    public function generate_post() {
        $t0 = microtime(true);
        $raw_input  = json_decode(file_get_contents('php://input'), true);
        $prompt     = $raw_input['prompt'] ?? $this->post('prompt');
        $context    = $raw_input['context'] ?? null;    // draft.header terakhir
        $history    = $raw_input['history'] ?? [];      // riwayat chat dari React
        $session_id = $raw_input['session_id'] ?? null; // BARU: ID percakapan utk log

        if (empty($prompt)) {
            $this->response(['status' => false, 'message' => 'Prompt kosong'], 400); return;
        }

        // ---------- STEP 1: EXTRACT + INTENT ----------
        $ai_data = $this->extract_with_gemini($prompt, $history, $context);
        if (isset($ai_data['error'])) {
            $this->log_chat($session_id, $prompt, 'ERROR', null, null, false, null, $ai_data['error'], $t0);
            $this->response(['status' => false, 'message' => $ai_data['error']], 500); return;
        }
        $intent = strtoupper($ai_data['intent'] ?? 'NEW_QUOTE');

        // ---------- ROUTING: SCHEDULE_ONLY — cuma cari jadwal, JANGAN bangun quotation ----------
        if ($intent === 'SCHEDULE_ONLY') {
            $sched_direction = strtoupper(trim($ai_data['shipment_direction'] ?? 'EXPORT'));
            $sched_dest      = strtoupper(trim($ai_data['destination'] ?? ''));
            $sched_via_hint  = strtoupper(trim($ai_data['schedule_via_hint'] ?? ''));
            $sched_ts_type   = ($sched_via_hint === 'SIN') ? 'VIA SIN' : (($sched_via_hint === 'HKG') ? 'VIA HKG' : null);

            $schedules = null;
            try {
                // service diisi 'LCL' sekadar placeholder -- tabel schedule tdk membedakan
                // LCL/FCL di level vessel, jadi ini aman dipakai apa pun kargo user nantinya.
                $schedules = $this->find_schedules($sched_direction, 'LCL', $sched_dest, $sched_ts_type);
            } catch (\Throwable $e) {
                log_message('error', 'GAIL find_schedules (SCHEDULE_ONLY) gagal: ' . $e->getMessage());
            }

            $pseudo_draft = ['destination_name' => $sched_dest, 'direction' => $sched_direction, 'schedules' => $schedules];
            $reply = $this->compose_reply($prompt, $history, $pseudo_draft, null, $intent);

            // Detail teknis partner/API disimpan di server log saja. User cukup mendapat
            // ringkasan natural dari compose_reply().
            if (is_array($schedules)) {
                $internal_errors = [];
                if (!empty($schedules['leg2_error'])) $internal_errors[] = $schedules['leg2_error'];
                if (!empty($schedules['sin_error'])) $internal_errors[] = 'SIN: ' . $schedules['sin_error'];
                if (!empty($schedules['hkg_error'])) $internal_errors[] = 'HKG: ' . $schedules['hkg_error'];
                foreach (($schedules['options'] ?? []) as $opt) {
                    if (!empty($opt['leg2_error'])) $internal_errors[] = ($opt['label'] ?? $opt['via'] ?? '') . ': ' . $opt['leg2_error'];
                }
                foreach (array_unique($internal_errors) as $err) {
                    log_message('error', 'GAIL schedule-only detail: ' . $err);
                }
            }

            $this->log_chat($session_id, $prompt, $intent, $ai_data, $reply, false, $schedules ? 'SCHEDULE_FOUND' : 'SCHEDULE_NOT_FOUND', null, $t0);
            $this->response([
                'status' => true,
                'intent' => $intent,
                'reply'  => $reply,
                'data'   => null // SENGAJA null: schedule-only TIDAK membuat/mengubah draft quotation
            ], 200);
            return;
        }

        // ---------- ROUTING: QUESTION / CHITCHAT tidak perlu rebuild draft ----------
        if ($intent === 'CHITCHAT' || ($intent === 'QUESTION' && !empty($raw_input['draft']))) {
            // Untuk QUESTION, gunakan draft penuh yang dikirim frontend sebagai sumber jawaban
            $existing_draft = $raw_input['draft'] ?? null;
            $reply = $this->compose_reply($prompt, $history, $existing_draft, $raw_input['rate_meta'] ?? null, $intent);
            $this->log_chat($session_id, $prompt, $intent, $ai_data, $reply, false, null, null, $t0);
            $this->response([
                'status' => true,
                'intent' => $intent,
                'reply'  => $reply,
                'data'   => null // draft tidak berubah
            ], 200);
            return;
        }

        // ---------- STEP 2: RETRIEVAL (NEW_QUOTE / UPDATE_QUOTE) ----------
        $raw_account   = $ai_data['account_name'] ?? '';
        $clean_account = trim(preg_replace('/^(PT\.?|CV\.?|UD\.?)\s+/i', '', $raw_account));

        $shipper = null; $shipper_id = null; $pickup_address = '';
        if (!empty($clean_account)) {
            $shipper = $this->db->like('shipper_name', $clean_account)->get('mstshipper')->row();
            if ($shipper) {
                $shipper_id = $shipper->id_shipper;
                $addr_row = $this->db->where('id_shipper', $shipper_id)
                    ->group_start()->where('address_type', 'PICKUP')->or_where('address_type', 'MAIN')->group_end()
                    ->get('mstshipper_address')->row();
                $pickup_address = ($addr_row && !empty($addr_row->address)) ? $addr_row->address : $shipper->shipper_address;
            }
        }

        $dest_name = strtoupper($ai_data['destination'] ?? '');
        $service   = strtoupper($ai_data['service_type'] ?? 'LCL');
        $qty       = floatval($ai_data['qty'] ?? 1);
        $uom       = strtoupper($ai_data['uom'] ?? 'CBM');
        $term      = strtoupper(trim((string)($ai_data['term'] ?? '')));
        // Normalisasi alias umum secara deterministik; selain alias ini term tidak ditebak.
        if (in_array($term, ['CNF', 'C&F'], true)) $term = 'CFR';
        $valid_incoterms = ['EXW','FCA','FAS','FOB','CFR','CIF','CPT','CIP','DAP','DPU','DDU','DDP'];

        $direction = strtoupper(trim($ai_data['shipment_direction'] ?? 'EXPORT'));
        $ctr_size  = strtoupper($ai_data['container_size'] ?? '');

        // Margin: bedakan "tidak disebut" (null) vs "0 / tanpa margin"
        $margin_raw = $ai_data['margin_percentage'] ?? null;
        $margin     = is_null($margin_raw) || $margin_raw === '' ? null : floatval($margin_raw);

        // Pickup tristate: true (minta) / false (antar sendiri) / null (belum ditanya).
        // EXW tetap TIDAK memaksa pickup: semua biaya EXW ada di sisi buyer.
        $pf = $ai_data['pickup_requested'] ?? null;
        if (is_string($pf)) {
            $l = strtolower(trim($pf));
            $pf = ($l === 'true') ? true : (($l === 'false') ? false : null);
        }
        $pickup_requested = ($pf === true) || $term === 'FCA';
        $pickup_declined  = ($pf === false) && $term !== 'FCA';

        // Security filing (AMS/ENS/AFR/ACI) berdasarkan negara tujuan, ditentukan Gemini
        $security_filing = strtoupper(trim($ai_data['security_filing'] ?? 'NONE'));
        if (!in_array($security_filing, ['AMS', 'ENS', 'AFR', 'ACI'])) $security_filing = 'NONE';

        // Khusus DDP: persen estimasi duty & mode penagihan destination
        $duty_pct  = floatval($ai_data['duty_estimate_pct'] ?? 0);
        if ($duty_pct <= 0) $duty_pct = $this->default_duty_estimate_pct;
        $dest_mode = strtoupper(trim($ai_data['dest_charge_mode'] ?? ''));
        if (!in_array($dest_mode, ['ESTIMATE', 'ACTUAL_RECEIPT'])) $dest_mode = 'ESTIMATE';

        // --- DG (Dangerous Goods) ---
        $dg_raw = $ai_data['is_dg'] ?? null;
        if (is_string($dg_raw)) $dg_raw = strtolower(trim($dg_raw)) === 'true';
        $is_dg     = ($dg_raw === true);
        $dg_class  = trim((string)($ai_data['dg_class'] ?? ''));
        $un_number = strtoupper(trim((string)($ai_data['un_number'] ?? '')));

        $user_pickup_addr = trim($ai_data['pickup_address'] ?? '');
        $pickup_area      = strtoupper(trim($ai_data['pickup_area'] ?? ''));
        if ($user_pickup_addr !== '') {
            $pickup_address = $user_pickup_addr; // alamat dari chat menang atas master
        }

        // Lingkup pekerjaan: clearance-only / trucking-only tidak boleh memunculkan freight
        $job_scope = strtoupper(trim($ai_data['job_scope'] ?? 'ALL_IN'));
        if (!in_array($job_scope, ['ALL_IN', 'CLEARANCE_ONLY', 'TRUCKING_ONLY'])) $job_scope = 'ALL_IN';
        if ($job_scope === 'TRUCKING_ONLY') { $pickup_requested = true; $pickup_declined = false; }
        $origin_port = strtoupper(trim($ai_data['origin_port'] ?? ''));

        // Freight ditagih ke customer kita atau tidak? (inti perbaikan incoterm)
        // EXPORT: shipper adalah customer → E/F-terms = Freight Collect (buyer yang bayar).
        // IMPORT: consignee adalah customer → justru E/F-terms yang membuat dia bayar freight.
        $ef_terms = ['EXW', 'FCA', 'FAS', 'FOB'];

        // Jangan mengambil keputusan komersial sebelum Incoterm diketahui.
        // null = belum bisa ditentukan; true/false baru dihitung setelah term tersedia.
        $freight_billed = null;
        if ($term !== '') {
            $freight_billed = ($job_scope === 'ALL_IN') && (($direction === 'EXPORT')
                ? !in_array($term, $ef_terms, true)
                : in_array($term, $ef_terms, true));
        }

        // ==========================================================
        // GERBANG KLARIFIKASI (SLOT-FILLING)
        // Jangan buru-buru bikin draft kalau info wajib belum lengkap.
        // ==========================================================
        $missing = [];
        if (empty($dest_name)) {
            if ($job_scope === 'TRUCKING_ONLY') {
                $missing[] = "Lokasi tujuan/drop-off trucking belum disebutkan. Tanyakan trucking akan dikirim ke mana (contoh: Tanjung Priok, CFS tertentu, depot, atau alamat gudang). Jangan menyebutnya POD bila konteksnya trucking-only.";
            } else {
                $missing[] = "Tujuan (POD) belum disebutkan.";
            }
        }

        // Untuk quotation ALL_IN, Incoterm adalah informasi komersial wajib.
        // Tidak boleh diasumsikan FOB (atau term lain) karena memengaruhi freight,
        // origin/destination charges, trucking, clearance, insurance, dll.
        if (
            $job_scope === 'ALL_IN'
            && $term === ''
            && in_array($intent, ['NEW_QUOTE', 'UPDATE_QUOTE'], true)
        ) {
            $missing[] = "Incoterm belum disebutkan. Tanyakan Incoterm yang digunakan (mis. EXW, FCA, FOB, CFR/CNF, CIF, CPT, CIP, DAP, DPU, DDP). Jangan mengasumsikan FOB atau term lain.";
        } elseif (
            $job_scope === 'ALL_IN'
            && $term !== ''
            && !in_array($term, $valid_incoterms, true)
            && in_array($intent, ['NEW_QUOTE', 'UPDATE_QUOTE'], true)
        ) {
            $missing[] = "Incoterm '$term' tidak dikenali oleh rule quotation. Minta user konfirmasi term yang benar. Jangan mengganti otomatis menjadi FOB.";
        }
        if ($direction === 'IMPORT' && $freight_billed === true && empty($origin_port) && $job_scope === 'ALL_IN' && $intent === 'NEW_QUOTE') {
            $missing[] = "Ini quotation IMPORT dan freight ditagih ke consignee (term $term), tapi asal barang (Port of Loading) belum disebut. Tanyakan dari pelabuhan/kota mana barang dikirim (cth: Shanghai, Qingdao) — dibutuhkan utk cari rate.";
        }
        // Term yang di matriks standar mengandung trucking (export side)
        $trucking_terms = ['FOB', 'FAS', 'CFR', 'CPT', 'CIF', 'CIP', 'DAP', 'DPU', 'DDU', 'DDP'];
        if ($direction === 'EXPORT' && $job_scope === 'ALL_IN' && in_array($term, $trucking_terms)
            && is_null($pf) && $term !== 'FCA' && $intent === 'NEW_QUOTE') {
            $missing[] = "Term $term biasanya termasuk trucking gudang→pelabuhan, tapi user belum menyebut. Tanyakan: apakah perlu pickup/trucking? Jika ya, dari kota/area mana? Jika barang diantar sendiri ke gudang/CFS, cukup jawab 'tanpa pickup'.";
        }
        if ($pickup_requested && empty($pickup_address) && $pickup_area === '') {
            $missing[] = "User minta layanan PICKUP tapi alamat/area pickup belum ada (tidak disebut di chat dan tidak ditemukan di master shipper). Tanyakan alamat atau minimal kota/kawasan lokasi pickup.";
        }
        if ($is_dg && $dg_class === '' && $un_number === '') {
            $missing[] = "Kargo terindikasi DG (Dangerous Goods) tapi Class IMO / UN Number belum disebut. Tanyakan: Class DG berapa (mis. Class 3 Flammable) dan/atau UN Number-nya, dan ingatkan bahwa MSDS wajib disiapkan karena rate & acceptance tergantung class.";
        }
        // Margin TIDAK lagi ditanyakan — jika tidak disebut, dipakai default 20% (dengan catatan ke sales).

        if (!empty($missing)) {
            $clarify_lang = $this->preferred_reply_language($prompt, $history);
            $clarify_language_instruction = $clarify_lang === 'EN'
                ? 'OUTPUT LANGUAGE: ENGLISH ONLY. Do not answer in Indonesian.'
                : 'OUTPUT LANGUAGE: BAHASA INDONESIA.';

            $clarify_system = <<<SYS
{$clarify_language_instruction}

Kamu adalah GAIL, asisten AI logistik Gateway Container Line. Nada santai-profesional, singkat, jelas, boleh emoji secukupnya.
ATURAN BAHASA: patuhi OUTPUT LANGUAGE di atas. Untuk pesan pendek/ambigu, bahasa sudah ditentukan dari riwayat user oleh sistem.
ATURAN SAPAAN: jangan membuka dengan "Halo", "Hai", "Hi", "Hello", atau sapaan waktu kecuali user memang sedang menyapa. Langsung ke inti klarifikasi.
Sistem mendeteksi permintaan quotation user BELUM lengkap. Tugasmu: dalam SATU pesan singkat & ramah,
konfirmasi dulu info yang SUDAH kamu tangkap (rute, layanan, qty, term) lalu tanyakan HANYA hal-hal yang kurang berikut ini.
JANGAN mengarang harga atau membuat draft dulu. Maksimal ~80 kata.
SYS;
            $clarify_prompt = "Info yang sudah tertangkap:\n" . json_encode($ai_data, JSON_UNESCAPED_UNICODE)
                            . "\n\nInfo yang KURANG dan harus ditanyakan:\n- " . implode("\n- ", $missing)
                            . "\n\nPesan asli user: " . $prompt;
            $res = $this->call_gemini($clarify_system, $this->build_gemini_contents($history, $clarify_prompt), false);
            if (isset($res['error'])) {
                $lang = $this->preferred_reply_language($prompt, $history);
                if ($lang === 'EN') {
                    // $missing berisi instruksi internal Indonesia; jangan kirim mentah ke user Inggris.
                    $reply = "I need a little more information before I can prepare the quotation. Please provide the missing commercial/shipment details requested above, especially the Incoterm if it has not been specified.";
                } else {
                    $reply = "Saya perlu sedikit informasi tambahan sebelum menyiapkan quotation. Mohon lengkapi detail shipment/komersial yang masih kurang, terutama Incoterm jika belum disebutkan.";
                }
            } else {
                $reply = $this->clean_reply_opening(trim($res['text']), $prompt);
            }

            $this->log_chat($session_id, $prompt, 'NEED_INFO', $ai_data, $reply, false, null, null, $t0);
            $this->response([
                'status'  => true,
                'intent'  => 'NEED_INFO',
                'reply'   => $reply,
                'pending' => $ai_data, // frontend kirim balik sebagai konteks di pesan berikutnya
                'data'    => null
            ], 200);
            return;
        }

        // Lolos gerbang: margin default 20% jika tidak disebutkan (dicatat utk sales)
        $margin_defaulted = false;
        if (is_null($margin)) { $margin = 20.0; $margin_defaulted = true; }

        $freight_items     = [];
        $origin_items      = [];
        $destination_items = [];   // BARU: section terpisah utk D-terms (DAP/DDU/DDP)
        $rate_meta     = [];   // BARU: kumpulan validity/remarks utk Gemini & frontend
        if ($margin_defaulted) {
            $rate_meta[] = [
                'source' => 'MARGIN',
                'note'   => "Margin tidak disebutkan → dipakai DEFAULT 20%. Sales dapat mengubahnya kapan saja, mis: \"ubah margin jadi 15%\"."
            ];
        }
        if ($is_dg) {
            $rate_meta[] = [
                'source'    => 'DG_CARGO',
                'dg_class'  => $dg_class !== '' ? $dg_class : null,
                'un_number' => $un_number !== '' ? $un_number : null,
                'note'      => "KARGO DG: MSDS wajib dikirim SEBELUM booking. Acceptance & final rate subject to carrier/agent approval berdasarkan Class" .
                               ($dg_class !== '' ? " $dg_class" : '') . ($un_number !== '' ? " / $un_number" : '') .
                               ". Kemasan harus memenuhi standar IMO (marking, labeling, packing group)."
            ];
        }
        $history_found = false;
        $price_found   = false;
        $today = date('Y-m-d');

        // ================= TIER 1: HISTORY INVOICE =================
        // GATE $direction==='EXPORT': tabel x_finance/trnstuffingplandetail di sini
        // adalah data EXPORT. Tanpa gate ini, quote IMPORT bisa nyasar match data export.
        if ($freight_billed && $direction === 'EXPORT' && $shipper_id && !empty($dest_name)) {
            $latest_inv = $this->db->select('xf.id as finance_id, xf.currency, xf.job_no, xf.date')
                ->from('x_finance xf')
                ->join('trnstuffingplandetail spd', 'spd.kode_ref = xf.job_no')
                ->where('spd.shipper', $shipper_id)
                ->like('spd.destination', $dest_name)
                ->where('xf.jenis', 'INVOICE')
                ->order_by('xf.date', 'DESC')->limit(1)->get()->row();

            if ($latest_inv) {
                $history_found = true; $price_found = true;
                $rate_meta[] = [
                    'source'  => 'HISTORY_INVOICE',
                    'ref'     => $latest_inv->job_no,
                    'inv_date'=> $latest_inv->date,
                    'note'    => 'Harga diambil dari invoice terakhir customer ini ke tujuan yang sama (tanpa margin tambahan).'
                ];
                $inv_items = $this->db->where('number', $latest_inv->finance_id)->get('x_finance_item')->result();
                foreach ($inv_items as $item) {
                    if (preg_match('/(PPN|VAT|TAX)/i', $item->item . ' ' . $item->code)) continue;
                    $curr = strtoupper(trim($item->currency));
                    if (empty($curr) || $curr === 'RP.' || $curr === 'RP') $curr = 'IDR';
                    $is_freight = (stripos($item->item, 'FREIGHT') !== false || stripos($item->item, 'O/F') !== false);
                    $row = [
                        'name' => $item->item,
                        'qty'  => $is_freight ? $qty : floatval($item->qty),
                        'uom'  => $uom,
                        'unit_price' => floatval($item->unit_price),
                        'amount' => $is_freight ? ($qty * floatval($item->unit_price)) : floatval($item->price),
                        'curr' => $curr,
                        'pricing_basis' => 'FIXED',
                        'charge_detail' => 'Ref: Invoice ' . $latest_inv->job_no
                    ];
                    if ($is_freight) $freight_items[] = $row; else $origin_items[] = $row;
                }
            }
        }

        // ================= TIER 2: SELLING RATE (validity dicek) =================
        // GATE $direction==='EXPORT': a_selling/a_selling_rate adalah data EXPORT.
        // PENTING -- baris of_emkl=1 (local charge) di query ini TIDAK difilter
        // destinasi sama sekali, jadi tanpa gate ini bisa "nyerempet" ke quote
        // IMPORT manapun asal nama shipper kebetulan cocok (bug nyata yg ditemukan).
        if ($freight_billed && $direction === 'EXPORT' && !$history_found && !empty($clean_account)) {
            $current_ymd = date('Ymd');
            $selling_rates = $this->db->select('asr.*, sel.kepada, sel.valid, sel.ref_no, sel.remarks as selling_remarks')
                ->from('a_selling_rate asr')
                ->join('a_selling sel', 'sel.id_selling = asr.id_selling') // alias "sel": hindari reserved word "as"
                ->where('sel.valid >=', $current_ymd)
                ->like('sel.kepada', $clean_account)
                ->where('asr.type', $service)
                ->group_start()
                    ->where('asr.of_emkl', 1)
                    ->or_group_start()->where('asr.of_emkl', 0)->like('asr.destination', $dest_name)->group_end()
                ->group_end()
                ->get()->result();

            if (!empty($selling_rates)) {
                foreach ($selling_rates as $sr) {
                    // Untuk FCL: cocokkan ukuran container jika user menyebutnya
                    if ($service === 'FCL' && !empty($ctr_size) && !empty($sr->container)
                        && stripos(str_replace(["'", " "], "", $sr->container), str_replace(["'", " "], "", $ctr_size)) === false) {
                        continue;
                    }
                    $curr = strtoupper(trim($sr->currency));
                    if (empty($curr) || $curr === 'RP.' || $curr === 'RP') $curr = 'IDR';

                    if ($sr->of_emkl == 0) {
                        $price_found = true;
                        $unit_price = floatval($sr->price);
                        $freight_items[] = [
                            'name' => ($sr->nama_service ?: 'Ocean Freight') . ' ' . $service .
                                      ($sr->container ? ' ' . $sr->container : '') . ' (To ' . $dest_name . ')',
                            'qty' => $qty, 'uom' => $uom,
                            'unit_price' => $unit_price, 'amount' => $qty * $unit_price,
                            'curr' => $curr, 'pricing_basis' => 'FIXED',
                            'charge_detail' => 'Ref: Selling Rate ' . $sr->ref_no
                        ];
                        $valid_fmt = preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '$3-$2-$1', $sr->valid);
                        $rate_meta[] = [
                            'source'   => 'SELLING_RATE',
                            'ref'      => $sr->ref_no,
                            'validity' => 'Berlaku sampai ' . $valid_fmt,
                            'remarks'  => trim(($sr->selling_remarks ?? '') . ' ' . ($sr->remarks ?? '')),
                            'kondisi'  => $sr->kondisi
                        ];
                    } else {
                        $basis = strtoupper(trim($sr->set_m3));
                        $item_qty = in_array($basis, ['M3','CBM','KGS','TON']) ? $qty : 1;
                        $origin_items[] = [
                            'name' => $sr->nama_service, 'qty' => $item_qty, 'uom' => $basis ?: 'JOB',
                            'unit_price' => floatval($sr->price), 'amount' => floatval($sr->price) * $item_qty,
                            'curr' => $curr, 'pricing_basis' => 'FIXED',
                            'charge_detail' => 'Ref: Selling Rate ' . $sr->ref_no
                        ];
                    }
                }
            }
        }

        // ================= TIER 1B: HISTORY IMPORT (ref_import + ref_import_detail) =================
        // Padanan Tier 1/2 di atas tapi utk arah IMPORT. consignee_id = mstshipper.id_shipper
        // adalah FK EKSAK (bukan fuzzy LIKE nama spt a_selling.kepada), jadi $shipper_id yg
        // sudah diresolve di atas bisa langsung dipakai tanpa pencocokan ulang.
        if ($freight_billed && !$history_found && !$price_found && $direction === 'IMPORT'
            && $shipper_id && !empty($origin_port)) {

            $current_ymd = date('Ymd');
            $latest_ref = $this->db->select('ri.id as ref_id, ri.date, ri.ref_no, ri.valid')
                ->from('ref_import ri')
                ->join('ref_import_detail rid', 'rid.data_id = ri.id')
                ->where('ri.consignee_id', $shipper_id)
                ->like('rid.port_loading', $origin_port)
                ->where('ri.valid >=', $current_ymd)
                ->group_by('ri.id')
                ->order_by('ri.date', 'DESC')
                ->limit(1)->get()->row();

            if ($latest_ref) {
                $history_found = true; $price_found = true;
                $ref_no_fmt = sprintf('%06d', (int)$latest_ref->ref_no);
                $rate_meta[] = [
                    'source'   => 'HISTORY_IMPORT',
                    'ref'      => $ref_no_fmt,
                    'inv_date' => $latest_ref->date,
                    'note'     => 'Harga diambil dari histori quotation import customer ini dari origin yang sama (tanpa margin tambahan).'
                ];

                // Layanan yg LAZIM flat per-shipment (bukan per CBM/container) — asumsi
                // konvensi industri, BUKAN dari kolom `mchgs` (maknanya tdk pasti, tdk dipakai
                // di sini spy tidak salah kali; sales bisa cek `charge_detail` utk verifikasi).
                $flat_fee_keywords = ['DO ', 'D/O', 'DOC FEE', 'DOCUMENT FEE', 'DOCUMENT', 'ADM FEE',
                                      'ADMINISTRATION', 'DECLARATION', 'PERMIT', 'TELEX', 'SEAL FEE', 'VGM'];

                $detail_rows = $this->db->where('data_id', $latest_ref->ref_id)->get('ref_import_detail')->result();
                foreach ($detail_rows as $item) {
                    $curr = strtoupper(trim($item->currency));
                    if (empty($curr) || $curr === 'RP.' || $curr === 'RP') $curr = 'IDR';
                    $unit_price = floatval($item->unit_price);
                    $is_freight = (stripos($item->service, 'FREIGHT') !== false || stripos($item->service, 'O/F') !== false);

                    $is_flat = false;
                    foreach ($flat_fee_keywords as $kw) {
                        if (stripos($item->service, $kw) !== false) { $is_flat = true; break; }
                    }
                    $item_qty = ($is_freight || !$is_flat) ? $qty : 1;

                    $row = [
                        'name' => trim($item->service) . ($item->port_loading ? ' (' . trim($item->port_loading) . ')' : ''),
                        'qty' => $item_qty, 'uom' => $uom,
                        'unit_price' => $unit_price, 'amount' => $item_qty * $unit_price,
                        'curr' => $curr, 'pricing_basis' => 'FIXED',
                        'charge_detail' => 'Ref: Import Quote ' . $ref_no_fmt .
                                          (trim($item->pre_col) !== '' ? ' (' . trim($item->pre_col) . ')' : '')
                    ];
                    if ($is_freight) $freight_items[] = $row; else $origin_items[] = $row;
                }
            }
        }

        // ================= TIER 3B: MASTER PRICING IMPORT =================
        // Sumber: import_ocean_freight_lcl_guidance/fcl_guidance (rate GUIDANCE resmi,
        // bukan rebate agent) utk freight, + import_destination_charge_items (biaya lokal
        // nyata CFS/Handling/dst, sisi COLOADER) utk origin charges.
        //
        // SENGAJA TIDAK memakai import_destination_charge_agents.rebate_nominate/
        // rebate_freehand sbg harga freight: teks aslinya literal berbunyi "Rebate USD
        // X/m3" -- kemungkinan besar itu KOMISI yg Gateway terima dari agent, BUKAN
        // harga jual freight (nilainya jauh di bawah harga pasar wajar, cth Qingdao
        // hanya USD 5/m3). Memakainya sbg harga jual berisiko salah kutip ke customer
        // sampai puluhan kali lipat. Menunggu konfirmasi bisnis sebelum dipakai.
        $import_local_charges_matched = false;
        if ($direction === 'IMPORT' && !empty($origin_port)) {

            // --- Ocean Freight dari guidance rate resmi ---
            if ($freight_billed && !$price_found) {
                if ($service === 'LCL') {
                    $lcl_guide = $this->db->like('pol_name', $origin_port)
                        ->where('is_active', 1)
                        ->where('of_per_cbm IS NOT NULL', null, false)
                        ->order_by('of_per_cbm', 'ASC')
                        ->limit(1)->get('import_ocean_freight_lcl_guidance')->row();
                    if ($lcl_guide) {
                        $price_found = true;
                        $unit_price = floatval($lcl_guide->of_per_cbm);
                        if ($margin > 0) $unit_price += abs($unit_price) * ($margin / 100);
                        $freight_items[] = [
                            'name' => 'OCEAN FREIGHT LCL (FROM ' . $origin_port . ')',
                            'qty' => $qty, 'uom' => $uom, 'unit_price' => $unit_price, 'amount' => $qty * $unit_price,
                            'curr' => 'USD', 'pricing_basis' => 'FIXED',
                            'charge_detail' => 'Ref: Import Guidance Rate' . (!empty($lcl_guide->agent_name) ? ' - ' . $lcl_guide->agent_name : '') .
                                              ($margin > 0 ? " (+ Margin $margin%)" : "")
                        ];
                        $rate_meta[] = [
                            'source' => 'MASTER_IMPORT_LCL', 'agent' => $lcl_guide->agent_name,
                            'pol' => $lcl_guide->pol_name, 'validity' => $lcl_guide->period_label,
                            'note' => 'Guidance rate resmi (bukan rebate/komisi agent).'
                        ];
                        $surcharge_cols = ['gri_per_cbm'=>'GRI','efs_per_cbm'=>'EFS','rr_per_cbm'=>'R/R',
                                          'lss_per_cbm'=>'LSS','pss_per_cbm'=>'PSS','fcs_per_cbm'=>'FCS','ets_per_cbm'=>'ETS'];
                        foreach ($surcharge_cols as $col => $label) {
                            if (!is_null($lcl_guide->$col) && floatval($lcl_guide->$col) != 0) {
                                $p = floatval($lcl_guide->$col);
                                if ($margin > 0) $p += abs($p) * ($margin / 100);
                                $freight_items[] = [
                                    'name' => "$label Surcharge", 'qty' => $qty, 'uom' => $uom,
                                    'unit_price' => $p, 'amount' => $p * $qty, 'curr' => 'USD',
                                    'pricing_basis' => 'FIXED', 'charge_detail' => 'Ref: Import Guidance Rate'
                                ];
                            }
                        }
                    }
                } elseif ($service === 'FCL') {
                    $pcol = ($ctr_size === '40FT') ? 'allin_40' : (($ctr_size === '40HC') ? 'allin_40hq' : 'allin_20');
                    $fcl_guide = $this->db->like('pol_name', $origin_port)
                        ->where('is_active', 1)
                        ->where("$pcol IS NOT NULL", null, false)
                        ->order_by($pcol, 'ASC')
                        ->limit(1)->get('import_ocean_freight_fcl_guidance')->row();
                    if ($fcl_guide) {
                        $price_found = true;
                        $unit_price = floatval($fcl_guide->$pcol);
                        if ($margin > 0) $unit_price += abs($unit_price) * ($margin / 100);
                        $freight_items[] = [
                            'name' => 'OCEAN FREIGHT FCL ALL-IN (FROM ' . $origin_port . ')' . ($ctr_size ? " $ctr_size" : ''),
                            'qty' => $qty, 'uom' => 'CNTR', 'unit_price' => $unit_price, 'amount' => $qty * $unit_price,
                            'curr' => 'USD', 'pricing_basis' => 'FIXED',
                            'charge_detail' => 'Ref: Import Guidance Rate' . (!empty($fcl_guide->carrier) ? ' - ' . $fcl_guide->carrier : '') .
                                              ($margin > 0 ? " (+ Margin $margin%)" : "")
                        ];
                        $rate_meta[] = [
                            'source' => 'MASTER_IMPORT_FCL', 'carrier' => $fcl_guide->carrier,
                            'agent' => $fcl_guide->agent_name, 'pol' => $fcl_guide->pol_name,
                            'validity' => $fcl_guide->period_label
                        ];
                    }
                }
            }

            // --- Biaya lokal origin (nyata, sisi COLOADER) — independen dari status freight,
            // TAPI dilewati jika Tier 1B (ref_import) sudah membawa item origin sendiri ---
            if (!$history_found) {
                $origin_agent = $this->db->like('origin_name', $origin_port)
                    ->where('is_active', 1)
                    ->order_by('rebate_nominate_amount', 'ASC')
                    ->limit(1)->get('import_destination_charge_agents')->row();

                if ($origin_agent) {
                    $local_items = $this->db->where('agent_id', $origin_agent->agent_id)
                        ->where('coloader_amount IS NOT NULL', null, false)
                        ->get('import_destination_charge_items')->result();

                    if (!empty($local_items)) {
                        $import_local_charges_matched = true;
                        $rate_meta[] = [
                            'source' => 'MASTER_IMPORT_LOCAL', 'agent' => $origin_agent->agent_name,
                            'note'   => "Biaya lokal origin dari data agent {$origin_agent->agent_name} ($origin_port). " .
                                        "Agent lain mungkin tersedia utk dibandingkan — cek master data bila perlu opsi lain."
                        ];
                        foreach ($local_items as $li) {
                            $p = floatval($li->coloader_amount);
                            if ($margin > 0) $p += abs($p) * ($margin / 100);
                            $item_qty = ($li->coloader_unit_basis_norm === 'CBM') ? $qty : 1;
                            $origin_items[] = [
                                'name' => $li->item_name, 'qty' => $item_qty, 'uom' => $li->coloader_unit_raw ?: 'JOB',
                                'unit_price' => $p, 'amount' => $p * $item_qty,
                                'curr' => $li->coloader_currency ?: 'USD', 'pricing_basis' => 'FIXED',
                                'charge_detail' => "Ref: {$origin_agent->agent_name} Local Charge" . ($margin > 0 ? " (+ Margin $margin%)" : "")
                            ];
                        }
                    }
                }
            }
        }

        // ================= TIER 3: MASTER PRICING (validity + remarks) =================
        $freight_qty = $qty; // bisa naik jika kena minimum charge T/S
        $ts_type_matched = null; // diisi Tier 3 TS_RATE bila rate ditemukan via VIA SIN/VIA HKG, dipakai utk pencarian schedule
        if ($freight_billed && !$price_found) {
            $unit_price = 0; $master_found = false;

            // GATE $direction==='EXPORT': freight_rates/freight_rate_lcl/freight_rate_details/
            // ts_rates semuanya master data EXPORT (destination_id = pelabuhan tujuan luar
            // negeri dari Indonesia). Tanpa gate ini, quote IMPORT bisa salah nyasar cocok
            // (mis. $dest_name='JAKARTA' kebetulan match sesuatu di ts_rates/freight_rates).
            // Utk IMPORT, satu-satunya sumber harga adalah Tier 1B (ref_import) di atas --
            // kalau itu tidak ketemu, freight IMPORT sengaja jatuh ke QUOTE_REQUIRED di bawah,
            // bukan menebak pakai data export.
            if ($direction === 'EXPORT') {
            if ($service === 'LCL') {
                // Cari rate sesuai jenis kargo: DG → coba rate khusus DG dulu, fallback GENCO+uplift
                $ct_order = $is_dg ? ['DG', 'GENCO'] : ['GENCO', 'DG'];
                $lcl_rate = null; $lcl_ct = null;
                foreach ($ct_order as $ct_try) {
                    $lcl_rate = $this->db->select('fr.id as rate_id, frl.price, frl.type_name, frl.transit_time,
                                               fr.cargo_type, fr.liner_name, fr.lcl_validity_from, fr.lcl_validity_to,
                                               fr.lcl_include_rate, fr.lcl_exclude_rate, fr.lcl_remarks')
                        ->from('freight_rate_lcl frl')
                        ->join('freight_rates fr', 'fr.id = frl.freight_rate_id')
                        ->join('tableName loc', 'loc.id = fr.destination_id')
                        ->like('loc.name', $dest_name)
                        ->where('fr.cargo_type', $ct_try)
                        ->group_start()  // rate harus masih berlaku (atau validity kosong)
                            ->where('fr.lcl_validity_to >=', $today)
                            ->or_where('fr.lcl_validity_to IS NULL', null, false)
                        ->group_end()
                        ->order_by('frl.is_priority', 'DESC')->order_by('frl.price', 'ASC')
                        ->limit(1)->get()->row();
                    if ($lcl_rate) { $lcl_ct = $ct_try; break; }
                }

                if ($lcl_rate) {
                    $unit_price = floatval($lcl_rate->price);
                    $master_found = true;

                    // DG tapi hanya rate GENCO tersedia → uplift estimasi
                    if ($is_dg && $lcl_ct === 'GENCO') {
                        $unit_price = $unit_price * (1 + $this->dg_lcl_uplift_pct / 100);
                        $rate_meta[] = ['source' => 'DG_SURCHARGE', 'note' =>
                            "Rate DG khusus tidak tersedia utk rute ini → O/F dihitung dari rate GENCO + uplift DG {$this->dg_lcl_uplift_pct}% (ESTIMASI, kisaran pasar 50–100%). Final rate wajib konfirmasi ke consol/carrier."];
                    } elseif ($is_dg && $lcl_ct === 'DG') {
                        $rate_meta[] = ['source' => 'DG_SURCHARGE', 'note' => 'Menggunakan rate khusus DG dari master (bukan estimasi).'];
                    }
                    $rate_meta[] = [
                        'source'   => 'MASTER_LCL',
                        'liner'    => $lcl_rate->liner_name,
                        'routing'  => $lcl_rate->type_name,
                        'validity' => ($lcl_rate->lcl_validity_from && $lcl_rate->lcl_validity_to)
                                      ? $lcl_rate->lcl_validity_from . ' s/d ' . $lcl_rate->lcl_validity_to : null,
                        'transit_time' => $lcl_rate->transit_time,
                        'include'  => $lcl_rate->lcl_include_rate,
                        'exclude'  => $lcl_rate->lcl_exclude_rate,
                        'remarks'  => $lcl_rate->lcl_remarks
                    ];
                }
            }

            if ($service === 'FCL') {
                // Harga FCL = SUM semua komponen di freight_rate_details (ALL-IN per container).
                // DG → coba rate sheet cargo_type DG dulu; fallback GENCO + DG Surcharge flat.
                $price_col = ($ctr_size === '40FT') ? 'frd.price_40' : (($ctr_size === '40HC') ? 'frd.price_40hc' : 'frd.price_20');
                $ct_order = $is_dg ? ['DG', 'GENCO'] : ['GENCO', 'DG'];
                $fcl_rate = null; $fcl_ct = null;
                foreach ($ct_order as $ct_try) {
                    $fcl_rate = $this->db->select("fr.id as rate_id, SUM($price_col) as allin_price,
                                               fr.cargo_type, fr.liner_name, fr.fcl_validity_from, fr.fcl_validity_to,
                                               fr.fcl_include_rate, fr.fcl_exclude_rate, fr.fcl_remarks,
                                               fr.fcl_free_time, fr.fcl_transit_time")
                        ->from('freight_rate_details frd')
                        ->join('freight_rates fr', 'fr.id = frd.freight_rate_id')
                        ->join('tableName loc', 'loc.id = fr.destination_id')
                        ->like('loc.name', $dest_name)
                        ->where('fr.cargo_type', $ct_try)
                        ->group_start()
                            ->where('fr.fcl_validity_to >=', $today)
                            ->or_where('fr.fcl_validity_to IS NULL', null, false)
                        ->group_end()
                        ->group_by('fr.id')
                        ->order_by('allin_price', 'ASC')->limit(1)->get()->row();
                    if ($fcl_rate) { $fcl_ct = $ct_try; break; }
                }

                if ($fcl_rate) {
                    $unit_price = floatval($fcl_rate->allin_price);
                    $master_found = true;

                    // DG tapi hanya rate GENCO → tambah baris DG Surcharge flat per container
                    if ($is_dg && $fcl_ct === 'GENCO') {
                        $sur = floatval($this->dg_fcl_surcharge);
                        if ($margin > 0) $sur += abs($sur) * ($margin / 100);
                        $freight_items[] = [
                            'name' => 'DG Surcharge' . ($dg_class !== '' ? " (Class $dg_class)" : '') . ' — Flat per Container',
                            'qty' => max(1, (int)$qty), 'uom' => 'CNTR',
                            'unit_price' => $sur, 'amount' => $sur * max(1, (int)$qty), 'curr' => 'USD',
                            'pricing_basis' => 'ESTIMATE',
                            'charge_detail' => "Estimasi (kisaran pasar USD 150–500+/cntr tergantung rute & class) — subject to carrier confirmation"
                        ];
                        $rate_meta[] = ['source' => 'DG_SURCHARGE', 'note' =>
                            "Rate DG khusus tidak tersedia → dipakai all-in GENCO + DG Surcharge flat USD {$this->dg_fcl_surcharge}/cntr (ESTIMASI). Final rate wajib konfirmasi carrier."];
                    } elseif ($is_dg && $fcl_ct === 'DG') {
                        $rate_meta[] = ['source' => 'DG_SURCHARGE', 'note' => 'Menggunakan rate sheet khusus DG dari master (bukan estimasi).'];
                    }
                    $rate_meta[] = [
                        'source'   => 'MASTER_FCL',
                        'liner'    => $fcl_rate->liner_name,
                        'container'=> $ctr_size ?: '20FT',
                        'basis'    => 'ALL-IN (ocean freight + doc fee liner + telex + seal + service charge dijumlahkan)',
                        'validity' => ($fcl_rate->fcl_validity_from && $fcl_rate->fcl_validity_to)
                                      ? $fcl_rate->fcl_validity_from . ' s/d ' . $fcl_rate->fcl_validity_to : null,
                        'free_time'=> $fcl_rate->fcl_free_time,
                        'transit_time' => $fcl_rate->fcl_transit_time,
                        'include'  => $fcl_rate->fcl_include_rate,
                        'exclude'  => $fcl_rate->fcl_exclude_rate,
                        'remarks'  => $fcl_rate->fcl_remarks
                    ];
                }
            }

            if ($service === 'AIR') {
                // Air freight = dihitung per berat (bukan CBM/container). Berat charge-able
                // = MAX(berat aktual, berat volumetrik). Standar IATA: 1 CBM = 167 KG.
                $actual_weight_kg = ($uom === 'KGS') ? $qty : 0;
                $volume_cbm_air = floatval($ai_data['volume_cbm'] ?? 0);
                if ($uom === 'CBM' && $volume_cbm_air <= 0) $volume_cbm_air = $qty; // qty itself = volume kalau uom CBM
                $volumetric_weight_kg = $volume_cbm_air > 0 ? $volume_cbm_air * 167 : 0;
                $chargeable_weight = max($actual_weight_kg, $volumetric_weight_kg);
                if ($chargeable_weight <= 0) $chargeable_weight = max($qty, 1); // fallback aman, jangan sampai 0

                $weight_basis_note = ($volumetric_weight_kg > $actual_weight_kg)
                    ? "Dihitung dari berat VOLUMETRIK ({$volume_cbm_air} CBM x 167 kg/CBM = {$volumetric_weight_kg} kg), lebih besar drpd berat aktual."
                    : (($actual_weight_kg > 0) ? "Menggunakan berat AKTUAL {$actual_weight_kg} kg." :
                       "Berat/volume tidak disebutkan jelas oleh user — cek ulang sblm quote final.");

                // Origin: bandara ASAL di Indonesia. Default Jakarta/CGK bila tdk disebut.
                // PENTING: andalkan KODE IATA sbg jangkar utama (exact match), BUKAN teks
                // kota ("JAKARTA" TIDAK pernah muncul di kolom origin -- isinya nama bandara
                // spt "SOEKARNO-HATTA INTER", jadi LIKE-text-match ke nama kota akan gagal).
                // Kode diresolusi dari MASTER ref_airport (municipality -> iata_code), BUKAN
                // ditebak Gemini -- jauh lebih reliable krn ref_airport data resmi/lengkap.
                $resolve_airport_code = function ($city_name, $default_code = null) {
                    $city_name = trim((string) $city_name);
                    if ($city_name === '') return $default_code;
                    $row = $this->db->like('municipality', $city_name)
                        ->where("iata_code IS NOT NULL AND iata_code != ''", null, false)
                        ->order_by("CASE type WHEN 'large_airport' THEN 1 WHEN 'medium_airport' THEN 2 " .
                                   "WHEN 'small_airport' THEN 3 ELSE 4 END", '', false)
                        ->limit(1)->get('ref_airport')->row();
                    return $row ? strtoupper(trim($row->iata_code)) : $default_code;
                };

                $air_origin_text = trim($ai_data['origin_airport'] ?? '');
                if ($air_origin_text === '') $air_origin_text = 'Jakarta'; // default kota asal
                $air_origin_code = $resolve_airport_code($air_origin_text, 'CGK'); // fallback CGK kalau ref_airport tak ketemu
                $air_dest_code = $resolve_airport_code($dest_name, null); // null kalau tdk ketemu -> andalkan LIKE text di bawah

                // SHC: pakai flag is_dg yg SUDAH ada di sistem (Class/UN Number sudah
                // dikonfirmasi lewat gerbang klarifikasi DG sebelumnya bila relevan).
                $shc_filter = $is_dg ? 'DGR' : 'GEN';

                // Kondisi origin: match kode IATA hasil resolusi ref_airport (utama) ATAU
                // teks kota mentah sbg jaring pengaman (jaga2 airline_cost pakai penamaan beda)
                $origin_cond = "(origin_code = " . $this->db->escape($air_origin_code) .
                               " OR origin LIKE " . $this->db->escape('%' . strtoupper($air_origin_text) . '%') . ")";
                // Kondisi destination: match kode IATA hasil resolusi (jika ref_airport ketemu) ATAU teks kota
                $dest_cond = "(destination LIKE " . $this->db->escape('%' . $dest_name . '%');
                if ($air_dest_code !== null) {
                    $dest_cond .= " OR destination_code = " . $this->db->escape($air_dest_code);
                }
                $dest_cond .= ")";

                $air_rate = $this->db->where('valid >=', $today)
                    ->where('shc', $shc_filter)
                    ->where($origin_cond, null, false)
                    ->where($dest_cond, null, false)
                    ->order_by('dasar', 'ASC') // termurah base rate kalau ada >1 airline utk rute yg sama
                    ->limit(1)->get('airline_cost')->row();

                if (!$air_rate && $is_dg) {
                    // Rate DG khusus tdk ada utk rute ini -- JANGAN pakai rate GEN + tebakan
                    // uplift (beda struktur weight-break sepenuhnya, beresiko tinggi utk DG).
                    // Peringatkan saja, biarkan QUOTE_REQUIRED.
                    $rate_meta[] = ['source' => 'DG_WARNING', 'note' =>
                        "Tidak ada rate air freight khusus DG (SHC=DGR) utk rute " . strtoupper($air_origin_text) . " - $dest_name. " .
                        "Rate general cargo TIDAK bisa dipakai sbg estimasi (struktur weight-break air DG berbeda total & terkait keselamatan). " .
                        "Wajib konfirmasi manual ke airline/GSA."];
                }

                if ($air_rate) {
                    $master_found = true;

                    // Pilih tarif per-kg sesuai weight-break yg berlaku utk berat charge-able
                    if ($chargeable_weight < 45)        $per_kg = floatval($air_rate->dasar);
                    elseif ($chargeable_weight < 100)   $per_kg = floatval($air_rate->empatlima);
                    elseif ($chargeable_weight < 300)   $per_kg = floatval($air_rate->seratus);
                    elseif ($chargeable_weight < 500)   $per_kg = floatval($air_rate->tigaratus);
                    elseif ($chargeable_weight < 1000)  $per_kg = floatval($air_rate->limaratus);
                    else                                 $per_kg = floatval($air_rate->seribu);

                    $computed_total = $chargeable_weight * $per_kg;
                    $minimum_awb = floatval($air_rate->minimum);
                    $minimum_applied = ($minimum_awb > 0 && $computed_total < $minimum_awb);
                    $freight_total = $minimum_applied ? $minimum_awb : $computed_total;
                    if ($margin > 0) $freight_total += abs($freight_total) * ($margin / 100);

                    if ($minimum_applied) {
                        $freight_items[] = [
                            'name' => 'AIR FREIGHT (' . strtoupper($air_origin_text) . ' - ' . $dest_name . ') — MINIMUM AWB CHARGE',
                            'qty' => 1, 'uom' => 'AWB', 'unit_price' => $freight_total, 'amount' => $freight_total,
                            'curr' => 'IDR', 'pricing_basis' => 'FIXED',
                            'charge_detail' => "Berat charge-able {$chargeable_weight} kg x " . number_format($per_kg, 0, ',', '.') .
                                              " = " . number_format($computed_total, 0, ',', '.') . " (di bawah minimum AWB, minimum yg dipakai)" .
                                              ($margin > 0 ? " (+ Margin $margin%)" : "")
                        ];
                    } else {
                        $freight_items[] = [
                            'name' => 'AIR FREIGHT (' . strtoupper($air_origin_text) . ' - ' . $dest_name . ')',
                            'qty' => $chargeable_weight, 'uom' => 'KG',
                            'unit_price' => $freight_total / max($chargeable_weight, 0.01),
                            'amount' => $freight_total, 'curr' => 'IDR', 'pricing_basis' => 'FIXED',
                            'charge_detail' => 'Ref: Airline Cost' . ($margin > 0 ? " (+ Margin $margin%)" : "")
                        ];
                    }

                    // Surcharge dari remarks (format konsisten "AWB: IDR X" / "ZB FEE: IDR Y/KG").
                    // Kalau format tdk cocok, JANGAN dipaksa -- cukup tampilkan remarks apa
                    // adanya di rate_meta spy sales bisa baca manual (fidelitas > tebakan).
                    $remarks_text = (string)($air_rate->remarks ?? '');
                    if (preg_match('/AWB\s*:\s*IDR\s*([0-9.,]+)/i', $remarks_text, $mm)) {
                        $awb_fee = (float) preg_replace('/[^0-9]/', '', $mm[1]);
                        if ($awb_fee > 0) {
                            if ($margin > 0) $awb_fee += abs($awb_fee) * ($margin / 100);
                            $freight_items[] = ['name' => 'AWB Fee', 'qty' => 1, 'uom' => 'AWB',
                                'unit_price' => $awb_fee, 'amount' => $awb_fee, 'curr' => 'IDR',
                                'pricing_basis' => 'FIXED', 'charge_detail' => 'Ref: Airline Cost Surcharge'];
                        }
                    }
                    if (preg_match('/ZB\s*FEE\s*:\s*IDR\s*([0-9.,]+)\s*\/\s*KG/i', $remarks_text, $mm)) {
                        $zb_fee = (float) preg_replace('/[^0-9]/', '', $mm[1]);
                        if ($zb_fee > 0) {
                            if ($margin > 0) $zb_fee += abs($zb_fee) * ($margin / 100);
                            $freight_items[] = ['name' => 'ZB Fee', 'qty' => $chargeable_weight, 'uom' => 'KG',
                                'unit_price' => $zb_fee, 'amount' => $zb_fee * $chargeable_weight, 'curr' => 'IDR',
                                'pricing_basis' => 'FIXED', 'charge_detail' => 'Ref: Airline Cost Surcharge'];
                        }
                    }

                    $rate_meta[] = [
                        'source'   => 'MASTER_AIR',
                        'airline'  => $air_rate->airline . (!empty($air_rate->airline_code) ? ' (' . $air_rate->airline_code . ')' : ''),
                        'commodity'=> $air_rate->commodity,
                        'origin'   => $air_rate->origin . ' (' . $air_origin_code . ')',
                        'destination' => $air_rate->destination . ($air_dest_code ? ' (' . $air_dest_code . ')' : ''),
                        'validity' => 'Berlaku sampai ' . $air_rate->valid,
                        'note'     => $weight_basis_note . (trim($remarks_text) !== '' ? ' Remarks airline: ' . trim($remarks_text) : '')
                    ];
                }
            }

            if (!$master_found && $service !== 'AIR') {
                // Fallback T/S rate — join header utk validity & agent, hormati minimum charge
                $ts_rate = $this->db->select('tsr.*, th.validity as header_validity, th.validity_date, th.agent_name, th.currency_code')
                    ->from('ts_rates tsr')
                    ->join('ts_tariff_headers th', 'th.id = tsr.tariff_header_id', 'left')
                    ->like('tsr.destination', $dest_name)
                    ->group_start()  // header validity masih berlaku (atau tidak ada header)
                        ->where('th.validity_date >=', $today)
                        ->or_where('th.validity_date IS NULL', null, false)
                    ->group_end()
                    ->order_by('tsr.freight_wm', 'ASC')
                    ->limit(1)->get()->row();
                if ($ts_rate) {
                    $unit_price = floatval($ts_rate->freight_wm);
                    $master_found = true;

                    // Cek acceptance DG di tarif T/S (X = agen tidak menerima)
                    if ($is_dg) {
                        $dg3 = strtoupper(trim((string)($ts_rate->dg_class3 ?? '')));
                        $dg2 = strtoupper(trim((string)($ts_rate->dg_class2 ?? '')));
                        if ($dg3 === 'X' && $dg2 === 'X') {
                            $rate_meta[] = ['source' => 'DG_WARNING', 'note' =>
                                "PERHATIAN: agen T/S rute ini TIDAK menerima kargo DG (ditandai X di tarif). Rate di bawah hanya berlaku utk general cargo — perlu cek rute/agen alternatif utk DG."];
                        } else {
                            $dg_tariff_note = [];
                            if ($dg3 !== '' && $dg3 !== 'X') $dg_tariff_note[] = "Class 3: $dg3";
                            if ($dg2 !== '' && $dg2 !== 'X') $dg_tariff_note[] = "Class 2: $dg2";
                            $rate_meta[] = ['source' => 'DG_SURCHARGE', 'note' =>
                                'Tarif DG dari agen T/S: ' . ($dg_tariff_note ? implode(', ', $dg_tariff_note) : 'cek dgn agen (kolom DG kosong)') . ' — subject to acceptance dgn MSDS.'];
                        }
                    }

                    // min_cbm = minimum chargeable volume, bukan syarat eligibilitas.
                    // Kiriman 1 CBM dengan min 3 CBM tetap boleh, tapi dihitung 3 CBM.
                    $min_cbm = floatval($ts_rate->min_cbm ?: 1);
                    if ($qty < $min_cbm) $freight_qty = $min_cbm;

                    $ts_type_matched = strtoupper(trim((string)$ts_rate->ts_type));

                    $rate_meta[] = [
                        'source'    => 'TS_RATE',
                        'ts_type'   => $ts_rate->ts_type,
                        'agent'     => $ts_rate->agent_name,
                        'currency'  => $ts_rate->currency_code ?: 'USD',
                        'validity'  => $ts_rate->header_validity
                                       . ($ts_rate->validity_date ? ' (s/d ' . $ts_rate->validity_date . ')' : ''),
                        'via'       => $ts_rate->via_port,
                        'min_cbm'   => $min_cbm . ' CBM' . ($qty < $min_cbm ? " (kiriman {$qty} CBM dikenakan minimum {$min_cbm} CBM)" : ''),
                        'transit_time' => trim(($ts_rate->transit_time_1st ?? '') . ($ts_rate->transit_time_2nd ? '+' . $ts_rate->transit_time_2nd : '') . ' hari'),
                        'frequency' => $ts_rate->frequency,
                        'fumigation'=> $ts_rate->fumigation,
                        'surcharge_ams' => $ts_rate->surcharge_ams,
                        'dg_class3' => $ts_rate->dg_class3,
                        'remarks'   => mb_substr(trim(($ts_rate->remarks ?? '') . "\n" . ($ts_rate->tariff_remarks ?? '')), 0, 600)
                    ];

                    // Surcharge per-CBM dari tarif T/S ikut ditagih jika ada
                    if (!is_null($ts_rate->surcharge_gri) && floatval($ts_rate->surcharge_gri) != 0) {
                        $p = floatval($ts_rate->surcharge_gri);
                        if ($margin > 0) $p += abs($p) * ($margin / 100);
                        $freight_items[] = ['name' => 'GRI Surcharge', 'qty' => $freight_qty, 'uom' => 'CBM',
                            'unit_price' => $p, 'amount' => $p * $freight_qty, 'curr' => 'USD',
                            'pricing_basis' => 'FIXED', 'charge_detail' => 'Ref: T/S Tariff'];
                    }
                    if (!is_null($ts_rate->surcharge_ddc) && floatval($ts_rate->surcharge_ddc) != 0) {
                        $p = floatval($ts_rate->surcharge_ddc);
                        if ($margin > 0) $p += abs($p) * ($margin / 100);
                        $freight_items[] = ['name' => 'DDC Surcharge', 'qty' => $freight_qty, 'uom' => 'CBM',
                            'unit_price' => $p, 'amount' => $p * $freight_qty, 'curr' => 'USD',
                            'pricing_basis' => 'FIXED', 'charge_detail' => 'Ref: T/S Tariff'];
                    }
                }
            }
            } // tutup gate $direction === 'EXPORT'

            if ($master_found && $margin > 0 && $service !== 'AIR') {
                $unit_price = $unit_price + abs($unit_price) * ($margin / 100);
            }

            if ($service !== 'AIR') {
                $freight_items[] = [
                    'name' => 'Ocean Freight ' . $service . ($is_dg ? ' DG' : '') . ($ctr_size ? ' ' . $ctr_size : '')
                              . ($service === 'FCL' && $master_found ? ' ALL-IN' : '') . ' (To ' . $dest_name . ')',
                    'qty' => $freight_qty, 'uom' => $uom,
                    'unit_price' => $unit_price, 'amount' => $freight_qty * $unit_price,
                    'curr' => 'USD',
                    'pricing_basis' => $master_found ? 'FIXED' : 'QUOTE_REQUIRED',
                    'charge_detail' => $master_found ? ('Ref: Master Pricing' . ($margin > 0 ? " (+ Margin $margin%)" : "")) : 'Rate belum tersedia - perlu request ke pricing team'
                ];
            } elseif (!$master_found) {
                // AIR tapi $air_rate tidak ketemu (rute/SHC tdk ada di master) -> tetap
                // tampilkan baris QUOTE_REQUIRED yg jelas, konsisten dgn mode lain.
                $freight_items[] = [
                    'name' => 'Air Freight' . ($is_dg ? ' DG' : '') . ' (To ' . $dest_name . ')',
                    'qty' => $qty, 'uom' => $uom, 'unit_price' => 0, 'amount' => 0, 'curr' => 'IDR',
                    'pricing_basis' => 'QUOTE_REQUIRED',
                    'charge_detail' => 'Rate belum tersedia utk rute/komoditas ini - perlu request ke pricing team/airline'
                ];
            }
        }

        // Freight Collect: term E/F → freight tidak ditagih ke customer, tampilkan sbg info.
        // Khusus scope CLEARANCE_ONLY / TRUCKING_ONLY: freight tidak relevan, jangan tampil.
        if (!$freight_billed && $job_scope === 'ALL_IN') {
            $payer = ($direction === 'EXPORT') ? "buyer/consignee" : "shipper di origin";
            $freight_label = ($service === 'AIR') ? 'Air Freight' : ('Ocean Freight ' . $service);
            $freight_items[] = [
                'name' => $freight_label . ' (To ' . $dest_name . ') — FREIGHT ' .
                          (($direction === 'EXPORT') ? 'COLLECT' : 'PREPAID BY SHIPPER'),
                'qty' => $qty, 'uom' => $uom, 'unit_price' => 0, 'amount' => 0, 'curr' => 'USD',
                'pricing_basis' => 'COLLECT',
                'charge_detail' => "Term $term: freight menjadi tanggungan $payer, tidak ditagihkan di quotation ini"
            ];
        }

        // ================= CLEARANCE + TRUCKING + INCOTERM MATRIX =================
        $clearance_code = ($direction === 'EXPORT' ? 'EXP_' : 'IMP_') . $service;
        $clearance_tariffs = $this->db->where('service_type', $clearance_code)->get('clearance_tariffs')->result();
        $is_fcl = ($service === 'FCL');

        // Dedupe pintar: item dari history invoice sering beda penamaan dengan template
        // (cth: "DOCUMENT FEE" vs "Origin Documentation / BL Fee") → cocokkan via sinonim.
        $item_synonyms = [
            ['THC', 'TERMINAL HANDLING'],
            ['DOCUMENT', 'DOKUMEN', 'BL FEE', 'B/L FEE', 'BILL OF LADING'],
            ['CLEARANCE', 'PEB', 'PIB'],
            ['TRUCKING', 'PICKUP', 'HAULAGE'],
            ['SEAL'],
            ['VGM'],
            ['INSURANCE', 'ASURANSI'],
            ['BAF', 'CAF', 'PEAK SEASON'],
            ['AMS', 'ENS', 'AFR', 'ACI', 'SECURITY FILING'],
            ['LOADING', 'ON BOARD', 'FORKLIFT', 'BURUH'],
        ];
        $addItem = function ($name, $pricingBasis = 'AT_COST', $currency = 'IDR') use (&$origin_items, $clearance_tariffs, $qty, $margin, $item_synonyms) {
            $uname = strtoupper($name);
            // Cari grup sinonim yang cocok dengan nama yang diminta
            $needles = [$uname];
            foreach ($item_synonyms as $group) {
                foreach ($group as $kw) {
                    if (strpos($uname, $kw) !== false) { $needles = $group; break 2; }
                }
            }
            foreach ($origin_items as $oi) {
                $existing = strtoupper($oi['name']);
                foreach ($needles as $kw) {
                    if (strpos($existing, $kw) !== false) return; // sudah ada item sejenis
                }
            }
            if (stripos($name, 'Customs Clearance') !== false && !empty($clearance_tariffs)) {
                foreach ($clearance_tariffs as $ct) {
                    $basis_raw   = trim($ct->unit_basis ?? 'job');
                    $basis_clean = trim(preg_replace('/^\d+\s*x?\s*/', '', $basis_raw)); // "1 container" → "container"
                    $basis_lower = strtolower($basis_clean !== '' ? $basis_clean : $basis_raw);
                    $item_qty = in_array($basis_lower, ['cbm', 'kgs', 'ton']) ? $qty : 1;
                    $comp_name = $ct->component_name . (!empty($ct->remarks) ? ' (' . $ct->remarks . ')' : '');
                    $base_price = floatval($ct->price_idr);
                    if ($margin > 0) $base_price += abs($base_price) * ($margin / 100);
                    $origin_items[] = [
                        'name' => $comp_name, 'qty' => $item_qty,
                        'uom' => strtoupper($basis_clean !== '' ? $basis_clean : 'JOB'),
                        'unit_price' => $base_price, 'amount' => $base_price * $item_qty,
                        'curr' => 'IDR',
                        'pricing_basis' => ($ct->is_at_cost === 'Y') ? 'AT_COST' : 'FIXED',
                        'charge_detail' => 'Ref: Master Clearance' . ($margin > 0 ? " (+ Margin $margin%)" : "")
                    ];
                }
                return;
            }
            $origin_items[] = ['name' => $name, 'qty' => 1, 'uom' => 'JOB', 'unit_price' => 0, 'amount' => 0,
                               'curr' => $currency, 'pricing_basis' => $pricingBasis, 'charge_detail' => ''];
        };

        // --- Trucking dari master trucking_rates ---
        // FCL: price_20 / price_40 sesuai ukuran container.
        // LCL: CDD <= 8 CBM, Fuso <= 16 CBM, selebihnya Wingbox.
        // Matching lokasi bertingkat:
        // 1) pickup_area langsung;
        // 2) full destination_name di alamat;
        // 3) fallback common city (suffix setelah " - ") HANYA jika rate armada yg
        //    dibutuhkan sama pada semua row city tersebut. Jika beda -> QUOTE_REQUIRED.
        $addTrucking = function ($label = 'Trucking / Haulage (Gudang → Pelabuhan)')
            use (&$origin_items, &$rate_meta, $qty, $is_fcl, $ctr_size, $margin, $pickup_area, $pickup_address) {

            foreach ($origin_items as $oi) {
                if (stripos($oi['name'], 'Trucking') !== false || stripos($oi['name'], 'Pickup') !== false) return;
            }

            $resolveVehiclePrice = function ($row) use ($is_fcl, $ctr_size, $qty) {
                if (!$row) return ['price' => 0, 'fleet' => null, 'field' => null];

                if ($is_fcl) {
                    $ctr = strtoupper(trim((string)$ctr_size));
                    $is40 = in_array($ctr, ['40FT', '40', '40HC', '40HCFT'], true);
                    return [
                        'price' => (float)($is40 ? ($row->price_40 ?? 0) : ($row->price_20 ?? 0)),
                        'fleet' => $is40 ? 'Trailer 40FT' : 'Trailer 20FT',
                        'field' => $is40 ? 'price_40' : 'price_20',
                    ];
                }

                if ($qty <= 8) {
                    return ['price' => (float)($row->cdd_price ?? 0), 'fleet' => 'CDD', 'field' => 'cdd_price'];
                }
                if ($qty <= 16) {
                    return ['price' => (float)($row->fuso_price ?? 0), 'fleet' => 'Fuso', 'field' => 'fuso_price'];
                }
                return ['price' => (float)($row->wb_price ?? 0), 'fleet' => 'Wingbox', 'field' => 'wb_price'];
            };

            $chooseIfSameNeededRate = function ($rows) use ($resolveVehiclePrice) {
                if (empty($rows)) return null;
                $unique = [];
                foreach ($rows as $r) {
                    $rp = $resolveVehiclePrice($r);
                    // Satu row kosong/0 berarti master belum lengkap -> jangan asumsi common rate.
                    if (($rp['price'] ?? 0) <= 0) return null;
                    $unique[number_format((float)$rp['price'], 4, '.', '')] = true;
                }
                return count($unique) === 1 ? $rows[0] : null;
            };

            $pickupAreaUpper = strtoupper(trim((string)$pickup_area));
            $pickupAddrUpper = strtoupper(trim((string)$pickup_address));
            $truck_row = null;
            $match_type = null;
            $matched_rows = [];
            $matched_city = null;

            // STEP 1 — area langsung, contoh CILEUNYI -> CILEUNYI - BANDUNG.
            if ($pickupAreaUpper !== '') {
                $rows = $this->db->like('destination_name', $pickupAreaUpper)->get('trucking_rates')->result();
                if (count($rows) === 1) {
                    $rp = $resolveVehiclePrice($rows[0]);
                    if (($rp['price'] ?? 0) > 0) {
                        $truck_row = $rows[0];
                        $matched_rows = $rows;
                        $match_type = 'AREA';
                    }
                } elseif (count($rows) > 1) {
                    $same = $chooseIfSameNeededRate($rows);
                    if ($same) {
                        $truck_row = $same;
                        $matched_rows = $rows;
                        $match_type = 'AREA_COMMON_RATE';
                    }
                }
            }

            // STEP 2 — full destination master terdapat di pickup address.
            if (!$truck_row && $pickupAddrUpper !== '') {
                $all = $this->db->get('trucking_rates')->result();
                foreach ($all as $ar) {
                    $dest = strtoupper(trim((string)($ar->destination_name ?? '')));
                    if ($dest !== '' && strpos($pickupAddrUpper, $dest) !== false) {
                        $rp = $resolveVehiclePrice($ar);
                        if (($rp['price'] ?? 0) > 0) {
                            $truck_row = $ar;
                            $matched_rows = [$ar];
                            $match_type = 'ADDRESS_EXACT';
                            break;
                        }
                    }
                }
            }

            // STEP 3 — common city fallback.
            // Master format lazim: "BUAH BATU - BANDUNG". Jika alamat mengandung BANDUNG,
            // kumpulkan SEMUA row berakhiran city tsb, lalu pakai hanya bila rate armada
            // yg dibutuhkan sama semuanya.
            if (!$truck_row && $pickupAddrUpper !== '') {
                $all = isset($all) ? $all : $this->db->get('trucking_rates')->result();
                $cities = [];
                foreach ($all as $ar) {
                    $dest = strtoupper(trim((string)($ar->destination_name ?? '')));
                    if ($dest === '') continue;
                    $parts = preg_split('/\s*-\s*/u', $dest);
                    $city = strtoupper(trim((string)end($parts)));
                    if ($city === '' || strlen($city) < 4) continue;
                    if (strpos($pickupAddrUpper, $city) !== false) $cities[$city] = true;
                }

                foreach (array_keys($cities) as $city) {
                    $rows = [];
                    foreach ($all as $ar) {
                        $dest = strtoupper(trim((string)($ar->destination_name ?? '')));
                        $parts = preg_split('/\s*-\s*/u', $dest);
                        $rowCity = strtoupper(trim((string)end($parts)));
                        if ($rowCity === $city) $rows[] = $ar;
                    }
                    $same = $chooseIfSameNeededRate($rows);
                    if ($same) {
                        $truck_row = $same;
                        $matched_rows = $rows;
                        $matched_city = $city;
                        $match_type = 'CITY_COMMON_RATE';
                        break;
                    }
                }
            }

            $loc = !empty($pickup_address)
                ? " — $pickup_address"
                : ($pickupAreaUpper !== '' ? " — $pickupAreaUpper" : '');

            if ($truck_row) {
                $resolved = $resolveVehiclePrice($truck_row);
                $base_price = (float)$resolved['price'];
                $price = $base_price;
                $fleet = $resolved['fleet'];
                if ($margin > 0) $price += abs($price) * ($margin / 100);

                $referenceName = $match_type === 'CITY_COMMON_RATE' && $matched_city
                    ? ($matched_city . ' COMMON RATE')
                    : (string)$truck_row->destination_name;

                $origin_items[] = [
                    'name' => $label . $loc . " [$fleet]",
                    'qty' => 1,
                    'uom' => $is_fcl ? 'CNTR' : 'TRIP',
                    'unit_price' => $price,
                    'amount' => $price,
                    'curr' => 'IDR',
                    'pricing_basis' => 'FIXED',
                    'charge_detail' => 'Ref: Trucking Rate ' . $referenceName . ($margin > 0 ? " (+ Margin $margin%)" : '')
                ];

                $rate_meta[] = [
                    'source' => 'TRUCKING_RATE',
                    'match_type' => $match_type,
                    'area' => $referenceName,
                    'fleet' => $fleet,
                    'base_price' => $base_price,
                    'matched_areas' => array_values(array_map(function ($r) {
                        return (string)($r->destination_name ?? '');
                    }, $matched_rows)),
                ];
                return;
            }

            $origin_items[] = [
                'name' => $label . $loc,
                'qty' => 1,
                'uom' => $is_fcl ? 'CNTR' : 'TRIP',
                'unit_price' => 0,
                'amount' => 0,
                'curr' => 'IDR',
                'pricing_basis' => 'QUOTE_REQUIRED',
                'charge_detail' => 'Pickup area does not have an unambiguous trucking rate in the master'
            ];
            $rate_meta[] = [
                'source' => 'TRUCKING_RATE',
                'area' => $pickupAreaUpper,
                'note' => "Rate trucking untuk area '$pickupAreaUpper' belum dapat dipastikan dari master; perlu konfirmasi pricing.",
            ];
        };

        // --- Helper: biaya standar dengan harga IDR (dedupe by name) ---
        $addStdCharge = function ($name, $price_idr, $item_qty = 1, $uom = 'JOB', $basis = 'FIXED') use (&$origin_items, $margin) {
            foreach ($origin_items as $oi) {
                if (stripos($oi['name'], $name) !== false || stripos($name, $oi['name']) !== false) return;
            }
            $p = floatval($price_idr);
            if ($basis === 'FIXED' && $margin > 0) $p += abs($p) * ($margin / 100);
            $origin_items[] = [
                'name' => $name, 'qty' => $item_qty, 'uom' => $uom,
                'unit_price' => $p, 'amount' => $p * $item_qty, 'curr' => 'IDR',
                'pricing_basis' => $basis,
                'charge_detail' => $basis === 'FIXED' ? ('Ref: Tarif Standar' . ($margin > 0 ? " (+ Margin $margin%)" : "")) : ''
            ];
        };

        // --- Helper: item destination (utk D-terms, section terpisah) ---
        $addDestItem = function ($name, $basis = 'AT_COST', $currency = 'USD', $detail = '') use (&$destination_items) {
            foreach ($destination_items as $di) {
                if (stripos($di['name'], $name) !== false) return;
            }
            $destination_items[] = [
                'name' => $name, 'qty' => 1, 'uom' => 'JOB',
                'unit_price' => 0, 'amount' => 0, 'curr' => $currency,
                'pricing_basis' => $basis, 'charge_detail' => $detail
            ];
        };

        // --- Biaya lokal khusus DG (dipanggil dari addOriginLocals bila is_dg) ---
        // LCL : DG Handling/Doc Fee (per BL), CFS DG Surcharge (per CBM), Sticker/Labeling (if any)
        // FCL : DG Approval/Declaration Fee (per BL), DG THC (lebih mahal ±20–50% dari THC umum)
        $addDgCharges = function () use (&$origin_items, $is_dg, $is_fcl, $dg_class, $qty, $margin) {
            if (!$is_dg) return;
            $cls = $dg_class !== '' ? " Class $dg_class" : '';

            $push = function ($name, $price, $item_qty, $uom, $basis, $curr, $detail) use (&$origin_items, $margin) {
                foreach ($origin_items as $oi) {
                    if (stripos($oi['name'], $name) !== false) return;
                }
                $p = floatval($price);
                if ($basis === 'FIXED' && $margin > 0) $p += abs($p) * ($margin / 100);
                $origin_items[] = [
                    'name' => $name, 'qty' => $item_qty, 'uom' => $uom,
                    'unit_price' => $p, 'amount' => $p * $item_qty, 'curr' => $curr,
                    'pricing_basis' => $basis, 'charge_detail' => $detail
                ];
            };

            if ($is_fcl) {
                $push('DG Approval / Declaration Fee', $this->dg_doc_fee_idr, 1, 'BL',
                    $this->dg_doc_fee_idr > 0 ? 'FIXED' : 'AT_COST', 'IDR',
                    'Pengecekan MSDS & pengajuan izin manifest DG ke carrier/otoritas pelabuhan');
                $push('DG THC (Terminal Handling - DG Yard)', 0, max(1, (int)$qty), 'CNTR', 'AT_COST', 'IDR',
                    'THC container DG ±20–50% lebih tinggi dari general cargo (penanganan & yard khusus)');
            } else {
                $push('DG Handling / Document Fee', $this->dg_doc_fee_idr, 1, 'BL',
                    $this->dg_doc_fee_idr > 0 ? 'FIXED' : 'AT_COST', 'IDR',
                    'Pemeriksaan MSDS & pengajuan DG Approval ke carrier — fixed per shipment/BL');
                $push('CFS DG Surcharge' . $cls, $this->dg_cfs_per_cbm_usd, max(1, $qty), 'CBM',
                    $this->dg_cfs_per_cbm_usd > 0 ? 'FIXED' : 'AT_COST', 'USD',
                    'Area penumpukan khusus DG di CFS origin & destination — per CBM/RT');
                $push('Sticker / Labeling Fee' . $cls, 0, 1, 'SHPT', 'IF_ANY', 'IDR',
                    'Penempelan label kelas bahaya IMO bila kemasan belum berlabel standar');
            }
        };

        // --- Bundle biaya lokal pelabuhan asal (tarif standar perusahaan) ---
        // FCL : Handling 150rb/cntr, Doc Fee 150rb, Lift On & Lift Off (as per receipt)
        // LCL : Doc Fee 150rb saja (freight sudah all-in per CBM)
        // + Trucking & PEB/clearance tergantung term & permintaan, + biaya DG bila kargo DG.
        $addOriginLocals = function () use ($addItem, $addTrucking, $addStdCharge, $addDgCharges, $is_fcl, $qty,
                                            $pickup_requested, $pickup_declined, $security_filing, $dest_name) {
            if ($is_fcl) {
                $addStdCharge('Handling Fee', $this->tarif_handling_fcl, max(1, (int)$qty), 'CNTR', 'FIXED');
                $addStdCharge('Documentation Fee', $this->tarif_doc_fee, 1, 'SHPT', 'FIXED');
                $addStdCharge('Lift On', 0, max(1, (int)$qty), 'CNTR', 'PER_RECEIPT');
                $addStdCharge('Lift Off', 0, max(1, (int)$qty), 'CNTR', 'PER_RECEIPT');
            } else {
                $addStdCharge('Documentation Fee', $this->tarif_doc_fee, 1, 'SHPT', 'FIXED');
            }
            if ($pickup_requested && !$pickup_declined) $addTrucking();
            $addItem('Export Customs Clearance', 'AT_COST');   // expand PEB dkk dari master clearance
            // Security filing HANYA untuk negara yang mewajibkan (AMS: USA/China,
            // ENS: EU/UK, AFR: Jepang, ACI: Kanada). Vietnam dll → tidak muncul.
            if ($security_filing !== 'NONE') {
                $addItem($security_filing . ' Fee (Security Filing - ' . $dest_name . ')', 'AT_COST', 'USD');
            }
            $addDgCharges(); // biaya khusus DG (no-op bila bukan DG)
        };

        if ($job_scope === 'CLEARANCE_ONLY') {
            // User HANYA minta jasa kepabeanan → cukup expand master clearance
            // (tanpa freight, tanpa THC/BL/VGM/destination handling)
            $addItem(($direction === 'EXPORT' ? 'Export' : 'Import') . ' Customs Clearance', 'AT_COST');
            if ($pickup_requested && !$pickup_declined) {
                $addTrucking($direction === 'EXPORT'
                    ? 'Trucking (Gudang → Pelabuhan)'
                    : 'Delivery Trucking (Pelabuhan → Gudang Consignee)');
            }
        } elseif ($job_scope === 'TRUCKING_ONLY') {
            // User HANYA minta harga trucking
            $addTrucking($direction === 'EXPORT'
                ? 'Trucking (Gudang → Pelabuhan)'
                : 'Delivery Trucking (Pelabuhan → Gudang Consignee)');
        } elseif ($direction === 'EXPORT') {
            switch ($term) {
                case 'EXW':
                    // Semua biaya dari gudang shipper s/d tujuan = tanggungan buyer.
                    $origin_items[] = [
                        'name' => 'EXW: Tidak ada biaya yang ditagihkan ke shipper — seluruh biaya menjadi tanggungan buyer',
                        'qty' => 1, 'uom' => 'JOB', 'unit_price' => 0, 'amount' => 0, 'curr' => 'IDR',
                        'pricing_basis' => 'INFO', 'charge_detail' => ''
                    ];
                    if ($pickup_requested) {
                        // User minta bantuan muat barang → biaya buruh/forklift lokal
                        $addItem('Loading Labour / Forklift di Gudang Shipper', 'AT_COST');
                    }
                    break;

                case 'FCA':
                    $addTrucking('Trucking ke Named Place / Depo');
                    $addItem('Export Customs Clearance', 'AT_COST');
                    // THC & freight tanggungan buyer — tidak ditagihkan.
                    break;

                case 'FAS':
                case 'FOB':
                    // Origin locals lengkap, freight TIDAK ditagih (sudah dipastikan $freight_billed=false)
                    $addOriginLocals();
                    $addItem('Loading / On Board Charges', 'AT_COST');
                    break;

                case 'CFR':
                case 'CPT':
                    $addOriginLocals();
                    break;

                case 'CIF':
                case 'CIP':
                    $addOriginLocals();
                    $addItem('Marine Insurance Premium', 'IF_ANY', 'USD');
                    break;

                case 'DAP':
                case 'DPU':
                case 'DDU':
                    // OPSI 1 — DAP/DDU: delivery ke destination yard TERMASUK,
                    // biaya lokal pelabuhan tujuan + duty + pajak = EXCLUDED (dibayar buyer langsung).
                    $addOriginLocals();
                    $addDestItem('Delivery to Destination Yard/Place — ' . $dest_name, 'AT_COST', 'USD',
                        'Included in DAP scope');
                    $addDestItem('Destination Local Port Charges', 'EXCLUDED', 'USD',
                        'To be paid directly by the Buyer to local customs/forwarder upon arrival');
                    $addDestItem('Import Duty & Local VAT/Taxes', 'EXCLUDED', 'USD',
                        'To be paid directly by the Buyer to local customs/forwarder upon arrival');
                    $rate_meta[] = ['source' => 'INCOTERM_NOTE', 'note' =>
                        "Total Price ($term $dest_name) — Inclusions: origin charges, ocean freight, and delivery to destination yard. " .
                        "Exclusions: destination local port charges, Import Duty, and local VAT/Taxes; these charges are to be paid directly by the Buyer to the local customs/forwarder upon arrival."];
                    break;

                case 'DDP':
                    $addOriginLocals();
                    if ($dest_mode === 'ACTUAL_RECEIPT') {
                        // OPSI 3 — Fasilitator: biaya tujuan & pajak ditagih TERPISAH sesuai kwitansi resmi.
                        $addDestItem('Destination Local Charges & Handling', 'PER_RECEIPT', 'USD',
                            'Billed separately based on actual official invoice from destination port authority');
                        $addDestItem('Import Duties & Taxes', 'PER_RECEIPT', 'USD',
                            'Billed separately based on actual official customs receipt');
                        $addDestItem('Delivery to Destination Warehouse — ' . $dest_name, 'AT_COST', 'USD');
                        $rate_meta[] = ['source' => 'INCOTERM_NOTE', 'note' =>
                            "DDP $dest_name (As Per Actual Receipt) — Destination Local Charges & Import Taxes are excluded from the main price and will be billed separately to the Buyer " .
                            "based on the actual official invoice issued by the destination port authority and customs. We can facilitate local clearance and handling at destination through our forwarder network."];
                    } else {
                        // OPSI 2 — Estimasi: duty & pajak dimasukkan sbg estimasi + klausul penyesuaian.
                        $addDestItem('Destination Local Charges', 'AT_COST', 'USD');
                        $addDestItem("Import Duties & Taxes (Estimated @ {$duty_pct}%)", 'ESTIMATE', 'USD',
                            'Adjusted per actual official receipts at time of customs clearance');
                        $addDestItem('Delivery to Destination Warehouse — ' . $dest_name, 'AT_COST', 'USD');
                        $rate_meta[] = ['source' => 'INCOTERM_NOTE', 'note' =>
                            "Total Price (DDP $dest_name) — Inclusive of estimated Duties & Taxes. IMPORTANT: Destination Local Charges, Import Duties, and Taxes are calculated based on current tariffs (estimated at {$duty_pct}%). " .
                            "Any significant deviation or government revaluation at the time of customs clearance will be adjusted accordingly based on actual official receipts."];
                    }
                    break;

                default:
                    // Term tidak dikenali → minimal origin locals agar tetap ada draft
                    $addOriginLocals();
                    break;
            }
        } else { // IMPORT (customer kita = consignee)
            // ==========================================================
            // MATRIX INCOTERM IMPORT — siapa yang dibebani biaya apa.
            // Sumber: matrix bisnis yang diberikan (lihat rate_meta INCOTERM_NOTE).
            // Freight sudah ditangani terpisah via $freight_billed di atas —
            // matrix ini HANYA utk 5 dimensi sisanya: insurance, destination
            // charges, import customs, duty/tax, delivery ke consignee.
            //
            // Nilai per dimensi:
            //   BILL     -> tagih AT_COST (biaya nyata, jumlah blm pasti)
            //   SKIP     -> tanggung jawab shipper/seller luar negeri,
            //               JANGAN ditagihkan & JANGAN ditampilkan sbg baris
            //               (bukan berarti jasanya tak ada, cuma di luar
            //               scope quotation utk importer — lihat instruksi)
            //   DEPENDS  -> tagih tapi kasih catatan cek 'named place' dulu
            //               (khusus CPT/CIP)
            //   OPTIONAL -> tawarkan sbg add-on (IF_ANY), tdk otomatis termasuk
            // CNF sudah dinormalisasi ke CFR sejak tahap ekstraksi.
            // DDU = istilah lama, diperlakukan spt DAP (customs & duty tetap
            // di buyer) sesuai instruksi eksplisit.
            // ==========================================================
            $import_matrix = [
                'EXW' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'OPTIONAL'],
                'FCA' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'OPTIONAL'],
                'FAS' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'OPTIONAL'],
                'FOB' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'OPTIONAL'],
                'CFR' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'OPTIONAL'],
                'CIF' => ['dest'=>'BILL',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'BILL',    'insurance'=>'SKIP'],
                'CPT' => ['dest'=>'DEPENDS', 'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'DEPENDS', 'insurance'=>'OPTIONAL'],
                'CIP' => ['dest'=>'DEPENDS', 'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'DEPENDS', 'insurance'=>'SKIP'],
                'DAP' => ['dest'=>'SKIP',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'SKIP',    'insurance'=>'SKIP'],
                'DPU' => ['dest'=>'SKIP',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'SKIP',    'insurance'=>'SKIP'],
                'DDU' => ['dest'=>'SKIP',    'customs'=>'BILL', 'duty'=>'BILL', 'delivery'=>'SKIP',    'insurance'=>'SKIP'],
                'DDP' => ['dest'=>'SKIP',    'customs'=>'SKIP', 'duty'=>'SKIP', 'delivery'=>'SKIP',    'insurance'=>'SKIP'],
            ];
            $m = $import_matrix[$term]; // aman: term ALL_IN sudah divalidasi di gerbang klarifikasi

            if ($term === 'DDU') {
                $rate_meta[] = ['source' => 'INCOTERM_NOTE', 'note' =>
                    "DDU adalah istilah Incoterms versi lama (sebelum revisi 2010, sudah digantikan DAP). Diperlakukan setara DAP: " .
                    "destination charges & delivery jadi tanggungan buyer langsung, namun Import Customs Clearance & Duty/Tax tetap ditagihkan sesuai kesepakatan."];
            }

            // Insurance
            if ($m['insurance'] === 'OPTIONAL') {
                $addItem('Cargo Insurance (optional, jika diminta)', 'IF_ANY', 'USD');
            }
            // insurance=SKIP -> shipper luar negeri sudah menyediakan, tidak ditampilkan

            // Destination Handling Charges — skip placeholder generik jika item lokal
            // NYATA sudah ada dari Tier 3B/1B (CFS/Handling/dst), supaya tdk dobel
            if ($m['dest'] === 'BILL') {
                if (!$import_local_charges_matched && !$history_found) $addItem('Destination Handling Charges', 'AT_COST');
            } elseif ($m['dest'] === 'DEPENDS') {
                if (!$import_local_charges_matched && !$history_found) $addItem('Destination Handling Charges (cakupan tergantung named place)', 'AT_COST');
                $rate_meta[] = ['source' => 'INCOTERM_NOTE', 'note' =>
                    "Term $term: cakupan destination charges tergantung 'named place' yang disepakati shipper-buyer. Konfirmasi ke sales/kontrak sebelum quote final."];
            }
            // dest=SKIP -> tanggung jawab shipper, tidak ditagihkan

            // Import Customs Clearance (auto-expand dari clearance_tariffs via $addItem)
            if ($m['customs'] === 'BILL') {
                $addItem('Import Customs Clearance', 'AT_COST');
            }

            // Import Duty & Tax
            if ($m['duty'] === 'BILL') {
                $addItem('Import Duty & Tax', 'AT_COST');
            }

            // Delivery ke gudang consignee
            if ($m['delivery'] === 'BILL') {
                if ($pickup_requested) $addTrucking('Delivery Trucking (Pelabuhan → Gudang Consignee)');
                else $addItem('Delivery to Consignee', 'AT_COST');
            } elseif ($m['delivery'] === 'DEPENDS') {
                if ($pickup_requested) $addTrucking('Delivery Trucking (Pelabuhan → Gudang Consignee, cek named place)');
                else $addItem('Delivery to Consignee (cakupan tergantung named place)', 'AT_COST');
            }
            // delivery=SKIP -> shipper yg deliver, tidak ditagihkan

            if ($term === 'DDP') {
                $origin_items[] = [
                    'name' => 'DDP: SELURUH BIAYA (FREIGHT, ASURANSI, DESTINATION CHARGES, CUSTOMS, DUTY/TAX, DELIVERY) SUDAH DITANGGUNG SHIPPER — TIDAK ADA TAGIHAN KE CONSIGNEE',
                    'qty' => 1, 'uom' => 'JOB', 'unit_price' => 0, 'amount' => 0, 'curr' => 'IDR',
                    'pricing_basis' => 'INFO', 'charge_detail' => ''
                ];
            }
        }


        // ================= FINAL DRAFT =================
        // Seragamkan penamaan item: UPPERCASE semua (konsisten dgn data invoice/master)
        foreach ($freight_items     as &$fi) { $fi['name'] = strtoupper($fi['name']); } unset($fi);
        foreach ($origin_items      as &$oi) { $oi['name'] = strtoupper($oi['name']); } unset($oi);
        foreach ($destination_items as &$di) { $di['name'] = strtoupper($di['name']); } unset($di);

        $final_account_name = $shipper ? $shipper->shipper_name : ($ai_data['account_name'] ?? 'Klien Baru');
        $header_currency = (!empty($freight_items) && !empty($freight_items[0]['curr'])) ? $freight_items[0]['curr'] : 'USD';

        // Cari jadwal kapal utk membantu sales -- di-wrap try/catch supaya kalau API
        // partner (docusense/charterlink) error/timeout, quotation TETAP tampil normal
        // (jadwal hanya pelengkap, jangan sampai menggagalkan seluruh response).
        $schedules = null;
        if ($job_scope === 'ALL_IN' && in_array($service, ['LCL', 'FCL'], true) && $dest_name !== '') {
            try {
                $schedules = $this->find_schedules($direction, $service, $dest_name, $ts_type_matched);
            } catch (\Throwable $e) {
                // Internal log only. Jangan tampilkan error API/debug ke user.
                log_message('error', 'GAIL find_schedules gagal: ' . $e->getMessage());
                $schedules = null;
            }
        }

        $final_draft = [
            'header' => [
                'account_name'     => $final_account_name,
                'shipment_type'    => $direction,
                'job_scope'        => $job_scope,
                'transport_mode'   => ($service === 'AIR') ? 'AIR' : 'SEA',
                'sea_type'         => $service,
                'margin_percentage'=> $margin,
                'is_dg'            => $is_dg,
                'dg_class'         => $dg_class !== '' ? $dg_class : null,
                'un_number'        => $un_number !== '' ? $un_number : null,
                'origin'           => ($direction === 'EXPORT') ? 'IDJKT' : '',
                'origin_name'      => ($direction === 'EXPORT') ? 'JAKARTA' : $origin_port,
                'destination'      => '',
                'destination_name' => $dest_name,
                'term'             => $term,
                'currency'         => $header_currency
            ],
            'freight'     => $freight_items,
            'origin'      => $origin_items,
            'destination' => $destination_items,
            'rate_meta'   => $rate_meta,   // BARU: frontend simpan ini utk pertanyaan lanjutan
            'schedules'   => $schedules    // BARU: jadwal kapal (direct/via/API partner)
        ];

        // ---------- STEP 3: SUSUN JAWABAN NATURAL ----------
        $reply = $this->compose_reply($prompt, $history, $final_draft, $rate_meta, $intent);

        // Error teknis schedule hanya masuk server log, tidak pernah dikirim ke chat user.
        if (is_array($schedules)) {
            $internal_schedule_errors = [];
            if (!empty($schedules['leg2_error'])) $internal_schedule_errors[] = $schedules['leg2_error'];
            if (!empty($schedules['sin_error'])) $internal_schedule_errors[] = 'SIN: ' . $schedules['sin_error'];
            if (!empty($schedules['hkg_error'])) $internal_schedule_errors[] = 'HKG: ' . $schedules['hkg_error'];
            foreach (($schedules['options'] ?? []) as $opt) {
                if (!empty($opt['leg2_error'])) $internal_schedule_errors[] = ($opt['label'] ?? $opt['via'] ?? '') . ': ' . $opt['leg2_error'];
            }
            foreach (array_unique($internal_schedule_errors) as $err) {
                log_message('error', 'GAIL schedule detail: ' . $err);
            }
        }

        // Sumber rate utama utk analisa (NOT_FOUND = peluang menambah master rate)
        $rate_source = 'NOT_FOUND';
        foreach ($rate_meta as $rm) {
            if (in_array($rm['source'] ?? '', ['HISTORY_INVOICE', 'HISTORY_IMPORT', 'SELLING_RATE',
                'MASTER_LCL', 'MASTER_FCL', 'MASTER_AIR', 'TS_RATE', 'MASTER_IMPORT_LCL', 'MASTER_IMPORT_FCL', 'MASTER_IMPORT_LOCAL'])) {
                $rate_source = $rm['source']; break;
            }
        }
        if (!$freight_billed) $rate_source = 'FREIGHT_COLLECT';

        $this->log_chat($session_id, $prompt, $intent, $ai_data, $reply, true, $rate_source, null, $t0);
        $this->response([
            'status'  => true,
            'version' => self::ENGINE_VERSION,
            'intent' => $intent,
            'reply'  => $reply,
            'data'   => $final_draft
        ], 200);
    }
}
