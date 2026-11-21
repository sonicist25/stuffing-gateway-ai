<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transhipment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Transhipment_model');
        // Load library/helper standar jika diperlukan
        $this->load->library('session');
        $this->load->database(); // aman dipanggil ulang meski 'database' sudah autoload
    }

    public function scheduleTranshipmentListing() {
        $destination_search = !empty($this->input->post('destination'))
			? strtoupper(trim($this->input->post('destination')))
			: "";

        $via_search = $this->input->post('via');

        $data['scheduleRecords'] = [];
        $data['api_status'] = ['ok' => false, 'error' => null];
        $data['search_dest'] = $destination_search;
        $data['search_via'] = $via_search;

        if (!empty($destination_search) && !empty($via_search)) {
            if ($via_search === 'SIN') {
                $data['api_status'] = $this->schedule_via_sin_api($destination_search);
            } else if ($via_search === 'HKG') {
                $data['api_status'] = $this->schedule_via_hkg_api($destination_search);
            }
            
            if ($data['api_status']['ok']) {
                $data['scheduleRecords'] = $data['api_status']['schedules'];
            }
        }

        $data['title'] = 'Transhipment Schedules';
        $data['menu_navigation'] = 'navigation'; // Sesuaikan dengan template GCL
        $data['main_view'] = 'schedule/schedulesTranshipment';
        $this->load->view('afg_template', $data);
    }
	
	public function ajax_search_destination() {
		$keyword = $this->input->get('q');
		$results = $this->Transhipment_model->search_destinations($keyword, 30);
		
		$data = [];
		foreach ($results as $row) {
			// PERUBAHAN: Gunakan 'code' sebagai ID (misal: MXZLO)
			$data[] = [
				'id'   => strtoupper($row['code']), 
				'text' => $row['name'] . ' (' . $row['country_id'] . ') - ' . $row['code']
			];
		}
		
		header('Content-Type: application/json');
		echo json_encode(['results' => $data]);
	}

	// Dipanggil oleh tombol "Cek Harga" di schedulesTranshipment.php.
	// Cari rate transhipment di ts_rates (join ts_tariff_headers utk currency/validity).
	// Match ketat ke destination/POD itu sendiri (lihat find_ts_rates) -- hasilnya array
	// krn satu POD bisa punya beberapa baris rate (mis. beda kelas DG), meski biasanya cuma 1.
	public function ajax_get_harga() {
		header('Content-Type: application/json');

		$via       = strtoupper(trim($this->input->get('via')));       // 'SIN' | 'HKG'
		$dest_code = strtoupper(trim($this->input->get('dest_code'))); // kode port yg dicari di combobox (mis. MXZLO)
		$pod_text  = strtoupper(trim($this->input->get('pod')));       // field 'pod' dari hasil schedule

		$ts_type = ($via === 'HKG') ? 'VIA HKG' : 'VIA SIN';

		$rates = $this->find_ts_rates($dest_code, $pod_text, $ts_type);

		if (empty($rates)) {
			$label = $pod_text ?: $dest_code;
			echo json_encode([
				'status'  => false,
				'message' => "Rate untuk \"$label\" ($ts_type) belum ada di tariff master kami. Cek manual ke tim pricing atau tambahkan datanya di ts_rates."
			]);
			return;
		}

		echo json_encode(['status' => true, 'rates' => $rates]);
	}

	// Match HANYA ke destination/POD itu sendiri -- BUKAN ke via_port. Search "MANZANILLO"
	// harus cuma nampilin rate yang tujuan akhirnya Manzanillo, bukan ikut narik semua
	// tujuan lain (mis. Huixquilucan) yang cuma numpang lewat situ sebagai transhipment port.
	private function find_ts_rates($dest_code, $pod_text, $ts_type) {
		$select = 'ts_rates.*, h.currency_code, h.validity, h.validity_date, h.agent_name';
		$base = function () use ($select, $ts_type) {
			return $this->db->select($select)
				->from('ts_rates')
				->join('ts_tariff_headers h', 'h.id = ts_rates.tariff_header_id', 'left')
				->where('ts_rates.ts_type', $ts_type);
		};

		if ($ts_type === 'VIA SIN') {
			if (empty($dest_code)) return [];
			return $base()
				->where('ts_rates.destination_code', $dest_code)
				->order_by('ts_rates.destination', 'ASC')
				->get()->result_array();
		}

		// VIA HKG -- ambil nama kota dari "CITY / COUNTRY"
		$city = trim(explode('/', (string) $pod_text)[0]);
		if ($city === '') return [];

		return $base()
			->where('ts_rates.destination', $city)
			->order_by('ts_rates.destination', 'ASC')
			->get()->result_array();
	}

/**
 * Bangun query string "equal(...)" sesuai grammar Appwrite.
 * Appwrite SDK selalu generate format: equal("attr", ["value"])
 * dengan spasi setelah koma — server memvalidasi format ini secara strict.
 */
private function aw_equal($attribute, $value) {
    $val = is_string($value) ? '"' . addslashes($value) . '"' : $value;
    return 'equal("' . $attribute . '", [' . $val . '])';
}

private function schedule_via_sin_api($dest_code, $limit = 10) {
    $base_url = "https://docusense-v2.fr8labs.co/v1/databases/sailing-schedule-ecosystem-db/collections/nvo_schedules_benkel/documents";
    
    // 1. Pastikan kode kota benar-benar bersih dari karakter enter/spasi tersembunyi
    $clean_dest = strtoupper(trim(preg_replace('/\s+/', '', $dest_code)));

    // 2. PENTING: Appwrite mewajibkan format persis "attr", [value] (ada spasi setelah koma)
    //    Format tanpa spasi akan ditolak server dengan "Invalid query: Syntax error".
    $params = [
        'queries' => [
            $this->aw_equal('tenant_id', 'benkel'),
            $this->aw_equal('pol_code', 'SGSIN'),
            $this->aw_equal('pod_code', $clean_dest),
            'limit(' . (int)$limit . ')'
        ]
    ];

    // 3. PENTING: browser (Appwrite SDK) encode spasi sebagai '+', bukan '%20'.
    //    RFC3986 akan hasilkan %20 dan bisa ditolak server. Gunakan RFC1738 agar identik.
    $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    
    $url = $base_url . '?' . $query_string;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // 4. HAPUS Content-Type: application/json karena ini adalah murni GET Request
     curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'X-Appwrite-Project: 691c2e820028aae56fb6',
		'X-Appwrite-Response-Format: 1.4.0',
		'Content-Type: application/json'
	]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => "cURL Error gagal terhubung ke Benkel.", 'schedules' => []];
    }

    $decoded = json_decode($response, true);

    // Tangkap error spesifik dari Appwrite jika masih terjadi
    if ($http_code >= 400) {
        $api_msg = $decoded['message'] ?? $response;
        return ['ok' => false, 'error' => "API Error HTTP $http_code: " . $api_msg, 'schedules' => []];
    }

    $rows = $decoded['documents'] ?? [];
    $out = [];
    $today = date('Y-m-d');

    foreach ($rows as $r) {
        $etd_date = substr((string) ($r['etd'] ?? ''), 0, 10);
        if ($etd_date === '' || $etd_date < $today) continue; 
        
        $out[] = [
            'vessel'      => $r['vessel'] ?? null, 
            'voyage'      => $r['voyage_no'] ?? null,
            'pol'         => $r['pol'] ?? 'SINGAPORE', 
            'pod'         => $r['pod'] ?? $clean_dest,
            'etd'         => $etd_date, 
            'eta'         => substr((string)($r['eta_dest'] ?? ''), 0, 10),
            'cfs_closing' => substr((string)($r['cutoff'] ?? ''), 0, 10),
            'si_cutoff'   => substr((string)($r['dg_cutoff'] ?? ''), 0, 10),
            'is_tba'      => stripos((string) ($r['vessel'] ?? ''), 'TBA') !== false,
        ];
        if (count($out) >= $limit) break;
    }

    usort($out, function ($a, $b) { return strcmp($a['etd'], $b['etd']); });
    
    if (empty($out)) {
        return ['ok' => false, 'schedules' => [], 'error' => "Jadwal Appwrite ke $clean_dest belum tersedia."];
    }
    
    return ['ok' => true, 'error' => null, 'schedules' => $out];
}

   private function schedule_via_hkg_api($dest_code, $limit = 10) {
    // 1. Dapatkan kota dan negara akurat berdasarkan kode (misal: MXZLO)
    $loc = $this->Transhipment_model->get_location_details($dest_code);
    $city_name = $loc['city'];       // Menghasilkan "MANZANILLO"
    $country_name = $loc['country']; // Menghasilkan "MEXICO" dari mstcountries
    
    $base_url = 'https://www.charterlinkgroup.com/Schedule/GetList';

    $fetch = function ($destination_param) use ($base_url, $limit) {
        $url = $base_url . '?' . http_build_query(['origin' => 'HONG KONG', 'destination' => $destination_param]);
        $resp = $this->http_get_json($url, 'CHARTERLINK');
        
        if (!$resp['ok']) return ['ok' => false, 'error' => $resp['error'], 'schedules' => []];
        
        $today = date('Y-m-d');
        $rows = $resp['json']['data'] ?? [];
        $out = [];
        
        foreach ($rows as $r) {
            $etd_date = substr((string) ($r['Etd'] ?? ''), 0, 10);
            if ($etd_date === '' || $etd_date < $today) continue; 
            
            $out[] = [
                'vessel'      => $r['Vessel'] ?? null, 
                'voyage'      => $r['Voyage'] ?? null,
                'pol'         => $r['pol'] ?? null, 
                'pod'         => $r['pod'] ?? null,      // format asli: "CITY / COUNTRY", cth "MANZANILLO / MEXICO"
                'pod_code'    => $r['podid'] ?? null,    // kode port asli, cth "MXZLO" (dulu tidak ditangkap)
                'etd'         => $etd_date, 
                'eta'         => substr((string)($r['Eta'] ?? ''), 0, 10),
                'cfs_closing' => substr((string)($r['CFS_CLOSING_DATE'] ?? ''), 0, 10),
                // CATATAN: response asli Charterlink TIDAK punya field SI_CUTOFF_DATE,
                // jadi si_cutoff akan selalu kosong -- ini bukan bug, memang datanya tidak ada.
                'si_cutoff'   => substr((string)($r['SI_CUTOFF_DATE'] ?? ''), 0, 10),
                'is_tba'      => stripos((string) ($r['Vessel'] ?? ''), 'TBA') !== false,
            ];
            if (count($out) >= $limit) break;
        }
        
        usort($out, function ($a, $b) { return strcmp($a['etd'], $b['etd']); });
        return ['ok' => true, 'error' => null, 'schedules' => $out];
    };

    // Percobaan utama dengan nama kota saja
    $attempt1 = $fetch($city_name);
    if ($attempt1['ok'] && !empty($attempt1['schedules'])) return $attempt1;

    // Jika gagal, pastikan negara tidak kosong lalu gunakan format KOTA / NEGARA
    if (!empty($country_name)) {
        $tried_destination = $city_name . ' / ' . $country_name;
        $attempt2 = $fetch($tried_destination);
        
        if ($attempt2['ok'] && !empty($attempt2['schedules'])) return $attempt2;
        
        if ($attempt2['ok']) {
            return ['ok' => false, 'schedules' => [], 'error' => "Pencarian kota '$tried_destination' berhasil namun 0 jadwal tersedia."];
        }
        $attempt2['error'] = "Percobaan format '$tried_destination': " . $attempt2['error'];
        return $attempt2;
    }

    $attempt1['error'] = $attempt1['ok'] ? "Jadwal kosong untuk '$city_name'." : $attempt1['error'];
    $attempt1['ok'] = false;
    return $attempt1;
}

	
	private function http_get_json($url, $agent = 'CHARTERLINK') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($agent === 'APPWRITE') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Appwrite-Project: 691c2e820028aae56fb6', // Pastikan ID ini terisi
            'Content-Type: application/json'
        ]);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => "cURL Error."];
    }

    $decoded = json_decode($response, true);
    
    // Jika error 400 ke atas, tangkap pesan asli dari server Appwrite
    if ($http_code >= 400) {
        $api_msg = $decoded['message'] ?? $response;
        return ['ok' => false, 'error' => "Error HTTP $http_code: " . $api_msg];
    }

    return ['ok' => true, 'json' => $decoded];
}
}