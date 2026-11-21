<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class C_inv_to_shipper extends CI_Controller{

	var $title ='Invoice to Shipper';

	// Faktor emisi rata-rata ocean freight (container vessel), GLEC Framework.
	// Ubah di sini kalau ada nilai yang lebih akurat untuk armada/rute tertentu.
	const CO2_EMISSION_FACTOR = 0.015; // kg CO2e / ton-km

	// Faktor emisi rata-rata road freight/trucking, GLEC Framework
	// "Road freight - General" (2019, well-to-wheel). Ganti kalau punya
	// angka yang lebih spesifik untuk jenis truk yang dipakai.
	const CO2_EMISSION_FACTOR_TRUCKING = 0.097; // kg CO2e / ton-km

	// Titik default Pelabuhan Tanjung Priok, dipakai sebagai origin jarak
	// trucking kalau tidak ada titik pickup lain yang lebih spesifik.
	// TODO: verifikasi/sesuaikan ke titik gerbang pelabuhan yang presisi.
	const TANJUNG_PRIOK_LAT = -6.1045;
	const TANJUNG_PRIOK_LNG = 106.8829;

	/**
	 * Jarak darat TETAP (fixed, BUKAN dari API) khusus rute Tanjung Priok <->
	 * BMJ Karawang (job 400/702/703, shipper BUKIT MURIA JAYA), dalam km.
	 *
	 * KENAPA di-hardcode, bukan panggil API routing kayak rute lain:
	 * ORS (api.heigit.org) terbukti BERULANG KALI ngasih jarak yang jauh
	 * meleset untuk rute spesifik ini, di 3 percobaan titik/profile beda:
	 *  1) driving-hgv dari Tanjung Priok (-6.1045,106.8829)  -> ~1.693 km
	 *     (nyasar muter lewat feri Bangka-Belitung + tol Sumatra)
	 *  2) driving-car dari titik yang sama                   -> ~1.592 km
	 *     (nyasar mirip, meski beda profile)
	 *  3) driving-hgv dari titik lain (gudang Airin, dekat
	 *     Priok, -6.1054476,106.9032381)                      -> ~153 km
	 *     (masih jauh lebih besar dari jarak riil)
	 * Disimpulkan graf jalan ORS untuk area pelabuhan Tanjung Priok memang
	 * tidak bisa diandalkan untuk rute ini, berapa pun titik/profile yang
	 * dicoba - jadi dipakai angka pasti yang sudah dicek manual di Google
	 * Maps (verified 2026-10-12, dari gudang Airin ke BMJ Karawang): 69.1 km.
	 *
	 * Kalau alamat BMJ berubah di kemudian hari, angka ini HARUS di-update
	 * manual juga (tidak otomatis ikut berubah seperti rute berbasis API).
	 * Shipper LAIN (bukan BMJ) di job 400 tetap pakai jarak dinamis dari
	 * API seperti biasa - fixed distance ini KHUSUS untuk BMJ saja.
	 */
	const PRIOK_BMJ_FIXED_DISTANCE_KM = 69.1;

	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->fungsi->get_define();
		$this->load->model(array('finance/m_inv_to_shipper','afg_m_cities','finance/m_finance_proses','afg_m_job_sheet','user_model'));
		$this->dbx = $this->m_inv_to_shipper->dbx; 
		$this->fungsi->dbx = $this->m_inv_to_shipper->dbx; 
	}

public function reopen_paid_invoice_revision()
	{
		$user_id = (int)$this->session->userdata('user_id');
		$allowed_user_ids = array(0, 107);

		if (!in_array($user_id, $allowed_user_ids, true)) {
			return $this->_json_post_response(403, array(
				'status'            => 0,
				'http'              => 403,
				'message'           => 'PAID_INVOICE_REVISION_ACCESS_DENIED',
				'requires_revision' => 0,
			));
		}
		$finance_id = (int)$this->input->post('finance_id', true);
		$reason     = trim((string)$this->input->post('reason', true));

		$result = $this->m_inv_to_shipper->reopen_paid_invoice_for_revision(
			$finance_id,
			$reason
		);

		$http = isset($result['http'])
			? (int)$result['http']
			: (!empty($result['status']) ? 200 : 422);

		return $this->_json_post_response($http, $result);
	}

	function post_invoice_to_ar()
	{
		$region_id  = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)$this->input->post('finance_id', true);
		$invoice_no = trim((string)$this->input->post('invoice_no', true));

		if ($region_id === '') {
			return $this->_json_post_response(422, [
				'status'  => '0',
				'message' => 'region_id session wajib tersedia',
			]);
		}

		$invoice = $this->m_inv_to_shipper->find_finance_for_post($finance_id, $invoice_no);

		if (!$invoice) {
			return $this->_json_post_response(404, [
				'status'  => '0',
				'message' => 'INVOICE_NOT_FOUND',
				'data'    => [
					'finance_id' => $finance_id,
					'invoice_no' => $invoice_no,
					'region_id'  => $region_id,
				],
			]);
		}

		$finance_id = (int)$invoice['id'];
		$invoice_no = trim((string)$invoice['number']);

		// Jakarta tetap menggunakan posting legacy melalui curl_helper.
		if (strcasecmp($region_id, 'Jakarta') === 0) {
			if (!function_exists('body_jasaweb_invoice')) {
				$this->load->helper('curl');
			}

			if (!function_exists('body_jasaweb_invoice')) {
				return $this->_json_post_response(500, [
					'status'  => '0',
					'message' => 'curl_helper/body_jasaweb_invoice tidak tersedia',
				]);
			}

			return body_jasaweb_invoice(
				['invoice' => $invoice_no],
				$this->m_inv_to_shipper->get_active_db()
			);
		}

		$data = $this->m_inv_to_shipper->post_invoice_to_ar([
			'finance_id' => $finance_id,
			'invoice_no' => $invoice_no,
		]);

		$ok   = !empty($data['status']);
		$http = isset($data['http']) ? (int)$data['http'] : ($ok ? 200 : 422);

		return $this->_json_post_response($http, $data);
	}

	private function _json_post_response($http, array $payload)
	{
		return $this->output
			->set_status_header((int)$http)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}
	
	
	function get_address() {
		$value = $this->m_inv_to_shipper->get_address($_GET,'');
		echo json_encode($value);
	}
	
	function get_nomer_invoice() {
		$value = $this->fungsi->get_nomer_invoice($_POST);
		echo json_encode(array('num_inv'=>$value,"jenis"=>$_POST["jenis"]));
	}	
	
	function get_last_inv_no()
	{
		
		$inv_last = $this->fungsi->get_new_num_inv($_POST);
		echo json_encode(array('new_no'=>$inv_last));
	}
	
	function index()
	{
		$this->fungsi->auth(40,'Invoice to Shipper');
		$data['title'] = $this->title;
		$data['h1_title'] ='INVOICE TO SHIPPER';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_inv_to_shipper';
		$this->load->view('afg_template', $data);
	}
	
	function get_table_satu()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:3;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'desc';	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;	 		
		$q = $this->m_inv_to_shipper->get_table_satu($_POST,false);
		$query = $this->m_inv_to_shipper->get_table_satu($_POST,true);
		$destInfo = $this->m_inv_to_shipper->get_destination(isset($_POST['job_no']) ? $_POST['job_no'] : '');
		$incoterms = isset($destInfo['incoterms']) ? $destInfo['incoterms'] : '';	
		$hitung = count($q);	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}	 
		if ($hal > $total_hal) $hal=$total_hal;				
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($query as $row) {
			$now = new DateTime();
			$dateString = trim($row->date); // buang spasi

			// coba deteksi format
			if (preg_match('/^\d{8}$/', $dateString)) {
				// format YYYYMMDD
				$dateFromDB = DateTime::createFromFormat('Ymd', $dateString);
			} elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
				// format YYYY-MM-DD
				$dateFromDB = DateTime::createFromFormat('Y-m-d', $dateString);
			} else {
				$dateFromDB = false;
			}

			if ($dateFromDB !== false) {
				$interval = $now->diff($dateFromDB);
				$aging = $interval->days;

				if ($aging > 60 && $row->status == 'unpaid') {
					$status = 'overdue';
				} else {
					$status = $row->status;
				}
			} else {
				// fallback jika format tidak valid
				$status = $row->status;
			}
		   $data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
												$row->job_no,
												$row->jenis,
												$row->number,
												$this->fungsi->jin_date_str($row->date),
												$row->shipper_name,
												$row->group,
												$row->kurs,
												$status,
												$row->created_by,
												$incoterms,
												$row->grand_total,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_table_dua()
	{
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:5;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;

		// Hitung total baris pakai COUNT(*) (bukan tarik semua baris lalu count() di PHP).
		$hitung = $this->m_inv_to_shipper->count_table_dua($_POST);
		// Baris untuk halaman ini saja.
		$query = $this->m_inv_to_shipper->get_table_dua($_POST,true);

		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}		
		if ($hal > $total_hal) $hal=$total_hal;	 
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;

		// Batch-fetch semua lookup yang dulu dipanggil per-baris (N+1 query),
		// sekarang cukup 3 query total untuk seluruh halaman.
		$salesIds  = array();
		$shipperNm = array();
		$codeJobs  = array();
		foreach($query as $row) {
			$salesIds[]  = $row->sales;
			$shipperNm[] = $row->shipper_print;
			$codeJobs[]  = $row->code_job;
		}
		$salesMap   = $this->m_inv_to_shipper->map_nama_sales($salesIds);
		$shipperMap = $this->m_inv_to_shipper->map_shipper_info($shipperNm);
		$blueMap    = $this->m_inv_to_shipper->map_blue_baru($codeJobs);

		$i=0;
		foreach($query as $row) {
			$shipperInfo = isset($shipperMap[$row->shipper_print]) ? $shipperMap[$row->shipper_print] : null;
			$shipperId      = isset($shipperInfo->shipper_id) ? $shipperInfo->shipper_id : '';
			$shipperAddress = isset($shipperInfo->shipper_address) ? $shipperInfo->shipper_address : '';
			// get_address('code') lama: kosongkan kalau nama shipper ambigu (>1 kecocokan).
			$shipperCodeStrict = ($shipperInfo && $shipperInfo->match_count == 1) ? $shipperInfo->shipper_id : '';

		   $data->rows[$i]['id']=$row->sp_detail_id;
		   $data->rows[$i]['cell']=array(
												$row->code_job, 																		
												$this->fungsi->jin_date_str($row->tanggal_job),
												$row->shipper_print,
												$row->destination,
												$row->carrier,
												$row->vessel.' '.$row->voyage,									
												$row->kurs,
												isset($salesMap[$row->sales]) ? $salesMap[$row->sales] : '',
												$shipperId,
												$row->shipper_print,
												$shipperAddress,
												$row->shipper_import_code,
												$row->shipper_import,
												$row->shipper_import_add,
												isset($blueMap[$row->code_job]) ? $blueMap[$row->code_job] : 0,
												$row->region_id,
												$shipperCodeStrict,
												$row->incoterms,
												//$row->shipper_import,
												//$row->shipper_import_add,
			);
		   $i++;
		}
		echo json_encode($data);
	}
	
	public function open_form_invoice()
{
    $post   = $this->input->post();
    $oper   = $post['oper'] ?? 'add';
    $job_no = $post['job_no'] ?? '';

    $data = [
        'job_no'          => $job_no,
        'shipper_name'    => '',
        'shipper_address' => '',
        'nama'            => '',
        'npwp'            => '',
        'alamat_npwp'     => '',
        'pph23'           => '',
        'id'              => 0,
    ];

    $option = [];

  if ($oper === 'add') {

    // default id
    $last = $this->fungsi->get_last_id();
    $data['id'] = $last ? ($last->row()->id + 1) : 1;

    // dropdown harus punya default kosong
    $option = [" " => " "];

    // ambil group dari DB
    $listGroup = $this->m_inv_to_shipper->getListGroup_fast(
        'inf_income_selling_price',
        $job_no
    );

    if ($listGroup && $listGroup->num_rows() > 0) {
        foreach ($listGroup->result() as $row) {
            $option[$row->g] = $row->g;
        }
    }

} else {
        // ====== MODE EDIT ======
        $inv_no = $post['inv_no'] ?? '';
        $group  = $post['group'] ?? '';

        $data['id'] = (int)($post['id'] ?? 0);
        $option     = [$group => $group];

        // Satu query saja untuk ambil semua field header invoice
        $invHeader = $this->m_inv_to_shipper->get_invoice_header($inv_no);

        if ($invHeader) {
            $data['shipper_name']    = $invHeader->shipper_name ?? '';
            $data['shipper_address'] = $invHeader->shipper_address ?? '';
            $data['nama']            = $invHeader->nama ?? '';
            $data['npwp']            = $invHeader->npwp ?? '';
            $data['alamat_npwp']     = $invHeader->alamat_npwp ?? '';
        }

        // pph23 masih via fungsi terpisah
        $data['pph23'] = $this->m_inv_to_shipper->get_pph([$inv_no]);
    }

    // ====== Common data ======
    $destInfo = $this->get_destination($job_no);   // dipanggil sekali saja
    $data['destination'] = is_array($destInfo) && isset($destInfo['destination'])
        ? $destInfo['destination']
        : '';
    $data['incoterms']   = is_array($destInfo) && isset($destInfo['incoterms'])
        ? $destInfo['incoterms']
        : '';

    $data['si_exportir'] = $this->m_inv_to_shipper->si_exportir($job_no);
    $data['option']      = $option;

    $this->load->view('finance/v_inv_to_shipper_editor', $data);
}


	
	function get_destination($kode_ref)
	{
		$data = $this->m_inv_to_shipper->get_destination($kode_ref);
		return $data;
	}

	/**
	 * Hitung Total Emisi CO2 untuk 1 invoice, dengan prioritas sumber data:
	 *
	 * 1) Data aktual dari sistem tracking (x_tracking.co2, fallback
	 *    x_tracking_export.co2) berdasarkan house B/L ($query['hb_l']) -
	 *    dipakai duluan kalau ada. (Khusus job trucking, langkah ini
	 *    dilewati - lihat get_total_co2_kg_trucking().)
	 * 2) Kalau tracking belum ada datanya, fallback ke estimasi hitungan:
	 *    Berat (ton) x Jarak (km, dari maritime API / tabel manual) x
	 *    Faktor Emisi GLEC ocean freight.
	 * 3) Kalau keduanya tidak tersedia, return null (badge tidak ditampilkan
	 *    sama sekali - lihat draw_co2_badge()/draw_co2_breakdown_table()).
	 *
	 * Return value: null, ATAU array dengan key:
	 *   'total_kg'       => total emisi CO2 (float, kg)
	 *   'weight_ton'     => berat kargo dalam ton (float), null kalau sumbernya
	 *                        data tracking (tidak ada rincian breakdown)
	 *   'distance_km'    => jarak tempuh dalam km (float), null kalau dari tracking
	 *   'factor'         => faktor emisi yang dipakai (float), null kalau dari tracking
	 *   'distance_label' => label rute untuk ditampilkan di breakdown (string), null
	 *                        kalau dari tracking
	 * (dulu fungsi ini return float/null saja - sekarang array/null, supaya
	 * draw_co2_breakdown_table() punya rincian Berat/Jarak/Faktor untuk BMJ.)
	 *
	 * $is_trucking = true -> pakai jalur perhitungan trucking (pelabuhan/kota
	 * asal ke tujuan) sepenuhnya, bukan jalur ocean freight di bawah ini.
	 */
	private function get_total_co2_kg($query, $kode_ref, $is_trucking = false, $shipper_name = '')
	{
		log_message('error', '[CO2_DEBUG] get_total_co2_kg() mulai. kode_ref='.$kode_ref.' hb_l='.($query['hb_l'] ?? '(kosong)').' is_trucking='.var_export($is_trucking, true));

		if ($is_trucking) {
			$job = substr($kode_ref, 4, 3);
			return $this->get_total_co2_kg_trucking($query, $job, $shipper_name);
		}

		// 1) Coba dari data tracking dulu
		$co2 = $this->m_inv_to_shipper->get_co2_emission($query['hb_l'] ?? '');
		log_message('error', '[CO2_DEBUG] hasil get_co2_emission() = '.var_export($co2, true));
		if ($co2 !== null && $co2 !== '') {
			log_message('error', '[CO2_DEBUG] pakai data tracking, total = '.$co2.' kg');
			return array(
				'total_kg'       => (float)$co2,
				'weight_ton'     => null,
				'distance_km'    => null,
				'factor'         => null,
				'distance_label' => null,
			);
		}

		// 2) Fallback ke estimasi: Berat x Jarak x Faktor Emisi
		$job = substr($kode_ref, 4, 3);
		if (in_array($job, array('300','301','302','400','402','600','900','901','902'))) {
			$pol = $query['destination'] ?? '';
			$pod = $query['port_dest'] ?? '';
		} else {
			$pol = $query['port_loading'] ?? '';
			$pod = $query['destination'] ?? '';
		}
		log_message('error', '[CO2_DEBUG] fallback estimasi. job='.$job.' pol='.$pol.' pod='.$pod);

		$weight_kg  = isset($query['weight']) ? (float)$query['weight'] : 0;
		$weight_ton = $weight_kg / 1000;
		log_message('error', '[CO2_DEBUG] weight_kg='.$weight_kg.' weight_ton='.$weight_ton);

		$distance_km = $this->m_inv_to_shipper->get_distance_km($pol, $pod);
		log_message('error', '[CO2_DEBUG] hasil get_distance_km() = '.var_export($distance_km, true));

		if (!$distance_km || $weight_ton <= 0) {
			log_message('error', '[CO2_DEBUG] STOP: distance_km atau weight_ton tidak valid -> total_kg = null (badge tidak akan tampil)');
			return null;
		}

		$total_kg = round($weight_ton * $distance_km * self::CO2_EMISSION_FACTOR, 1);
		log_message('error', '[CO2_DEBUG] hasil akhir estimasi total_kg = '.$total_kg);

		return array(
			'total_kg'       => $total_kg,
			'weight_ton'     => $weight_ton,
			'distance_km'    => $distance_km,
			'factor'         => self::CO2_EMISSION_FACTOR,
			'distance_label' => 'Estimasi rute Laut '.strtoupper($pol).' - '.strtoupper($pod),
		);
	}

	/**
	 * Hitung Total Emisi CO2 untuk job AIR FREIGHT (200/600).
	 *
	 * BEDA dari ocean/trucking: di sini TIDAK dihitung manual (weight x
	 * distance x factor GLEC) - nilai diambil LANGSUNG dari cache hasil
	 * tracking AWB (`air_tracking_cache`, lihat
	 * M_inv_to_shipper::get_air_tracking_co2()), yang sudah menghitung
	 * carbonEmission riil per-leg penerbangan lewat API eksternal. Ini
	 * LEBIH akurat daripada estimasi UN/LOCODE yang dipakai jalur ocean.
	 *
	 * AWB yang dicocokkan ke cache ikut pilihan radio MAWB/HAWB yang sudah
	 * ada di editor invoice untuk job AF (lihat get_awb_type_from_get()) -
	 * 'mawb' -> trnstuffingplan.mother_bl, 'hawb' -> trnstuffingplandetail.hb_l.
	 *
	 * Karena nilainya bukan hasil rumus (bukan weight x distance x factor),
	 * breakdown dikembalikan dengan distance_km/weight_ton/factor = null -
	 * sama seperti kasus "data tracking" di get_total_co2_kg() - sehingga
	 * draw_co2_breakdown_table() otomatis fallback ke badge 1-baris biasa
	 * (tidak memaksakan tabel breakdown untuk angka yang bukan estimasi
	 * rumus kami sendiri).
	 *
	 * Return null kalau belum ada cache tracking untuk kode_ref ini (belum
	 * di-fetch, atau AWB belum diisi/tidak ketemu) - badge tidak tampil.
	 */
	private function get_total_co2_kg_air($query, $kode_ref)
	{
		$region = $this->session->userdata('region_id');
		$awb_type = $this->get_awb_type_from_get();
		$awb = ($awb_type === 'hawb') ? ($query['hb_l'] ?? '') : ($query['mother_bl'] ?? '');
		$sp_id = $query['sp_id'] ?? null;

		log_message('error', '[CO2_DEBUG][air] get_total_co2_kg_air() kode_ref='.$kode_ref.' awb_type='.$awb_type.' awb="'.$awb.'" sp_id='.var_export($sp_id, true));

		$result = $this->m_inv_to_shipper->get_air_tracking_co2($kode_ref, $region, $awb, $sp_id);
		log_message('error', '[CO2_DEBUG][air] hasil get_air_tracking_co2() = '.var_export($result, true));

		if ($result === null || $result['total_kg'] === null) {
			return null;
		}

		return array(
			'total_kg'       => $result['total_kg'],
			'weight_ton'     => null,
			'distance_km'    => null,
			'factor'         => null,
			'distance_label' => null,
		);
	}

	/**
	 * Hitung Total Emisi CO2 untuk job trucking, dengan 2 mode:
	 *
	 * Mode 1 - job 400/702/703 (pelabuhan -> alamat shipper):
	 *   Berat (ton) x Jarak darat (km, Tanjung Priok -> alamat shipper via
	 *   OpenRouteService driving-car) x Faktor Emisi GLEC road freight.
	 *   TIDAK memakai data tracking (x_tracking) sama sekali.
	 *
	 * Mode 2 - job 900/901/902 (trucking antar-kota, mis. Karawang -> Pematang
	 *   Siantar), KHUSUS shipper BUKIT MURIA JAYA:
	 *   Berat (ton) x Jarak darat (km, alamat shipper -> kota tujuan via
	 *   OpenRouteService driving-hgv, lihat catatan di
	 *   M_inv_to_shipper::call_road_distance_api() kenapa hgv masih dipakai
	 *   khusus di sini) x Faktor Emisi GLEC road freight.
	 *
	 * Shipper belum punya titik lat/long terdaftar di mstshipper_address, atau
	 * kota tujuan tidak ketemu di master kota (mode 2) -> return null (badge
	 * tidak tampil). Sesuai arahan: untuk sekarang cuma data yang sudah punya
	 * koordinat yang dapat badge/breakdown CO2 ini.
	 *
	 * Ada sanity-check tambahan: kalau jarak hasil API jauh lebih besar dari
	 * jarak garis-lurus (haversine) yang wajar, hasilnya DIBUANG (return null)
	 * daripada menampilkan angka yang jelas salah - ini jaring pengaman untuk
	 * bug ORS driving-hgv yang pernah ditemukan (rute muter lewat Bangka-
	 * Belitung/Sumatra untuk rute pendek Priok-Karawang).
	 */
	private function get_total_co2_kg_trucking($query, $job = '400', $shipper_name = '')
	{
		$weight_kg  = isset($query['weight']) ? (float)$query['weight'] : 0;

		// Tambahkan filter: weight harus > 1
		if ($weight_kg <= 1) {
			log_message('error', '[CO2_DEBUG][trucking] STOP: berat <= 1 kg -> total_kg = null');
			return null;
		}

		$weight_ton = $weight_kg / 1000;
		log_message('error', '[CO2_DEBUG][trucking] weight_kg='.$weight_kg.' weight_ton='.$weight_ton);

		if ($weight_ton <= 0) {
			log_message('error', '[CO2_DEBUG][trucking] STOP: berat tidak valid -> total_kg = null');
			return null;
		}

		$shipper_code  = trim((string)($query['shipper'] ?? ''));
		$shipper_coord = $this->m_inv_to_shipper->get_shipper_coordinates($shipper_code);
		log_message('error', '[CO2_DEBUG][trucking] shipper_code="'.$shipper_code.'" koordinat='.var_export($shipper_coord, true));

		if (!$shipper_coord) {
			log_message('error', '[CO2_DEBUG][trucking] STOP: shipper belum punya lat/long terdaftar -> total_kg = null');
			return null;
		}

		$is_city_to_city = ($job === '900' || $job === '901' || $job === '902');

		if ($is_city_to_city) {
			// Mode 2: origin = shipper sendiri, destination = kota tujuan.
			$origin_lat = $shipper_coord['lat'];
			$origin_lng = $shipper_coord['lng'];

			$dest_city = trim((string)($query['destination'] ?? ''));
			$dest_coord = $this->m_inv_to_shipper->get_city_coordinates($dest_city);
			log_message('error', '[CO2_DEBUG][trucking][900] dest_city="'.$dest_city.'" koordinat='.var_export($dest_coord, true));

			if (!$dest_coord) {
				log_message('error', '[CO2_DEBUG][trucking][900] STOP: kota tujuan tidak ketemu di master -> total_kg = null');
				return null;
			}

			$dest_lat = $dest_coord['lat'];
			$dest_lng = $dest_coord['lng'];
			$profile  = 'driving-hgv';

			$origin_label = $shipper_name !== '' ? $shipper_name : 'Shipper';
			$dest_label   = $dest_city !== '' ? strtoupper($dest_city) : 'Kota tujuan';
		} else {
			// Mode 1: origin = Tanjung Priok (konstan), destination = shipper.

			// Khusus BMJ: rute Tanjung Priok -> BMJ Karawang ini SELALU sama,
			// dan API ORS terbukti berulang kali nyasar untuk rute ini (lihat
			// komentar di const PRIOK_BMJ_FIXED_DISTANCE_KM) - jadi pakai jarak
			// tetap yang sudah dicek manual di Google Maps, TANPA panggil API
			// sama sekali (dan tanpa perlu sanity-check, karena bukan hasil API).
			$is_bmj_route = (stripos((string)$shipper_name, 'BUKIT MURIA JAYA') !== false);
			if ($is_bmj_route) {
				$distance_km = self::PRIOK_BMJ_FIXED_DISTANCE_KM;
				log_message('error', '[CO2_DEBUG][trucking] shipper BMJ -> pakai jarak tetap (bukan API) = '.$distance_km.' km');

				$total_kg = round($weight_ton * $distance_km * self::CO2_EMISSION_FACTOR_TRUCKING, 1);
				log_message('error', '[CO2_DEBUG][trucking] hasil akhir total_kg (jarak tetap) = '.$total_kg);

				return array(
					'total_kg'       => $total_kg,
					'weight_ton'     => $weight_ton,
					'distance_km'    => $distance_km,
					'factor'         => self::CO2_EMISSION_FACTOR_TRUCKING,
					'distance_label' => 'Jarak tetap (terverifikasi Google Maps) TANJUNG PRIOK - BMJ KARAWANG',
				);
			}

			$origin_lat = self::TANJUNG_PRIOK_LAT;
			$origin_lng = self::TANJUNG_PRIOK_LNG;

			$dest_lat = $shipper_coord['lat'];
			$dest_lng = $shipper_coord['lng'];
			$profile  = 'driving-car';

			$origin_label = 'Tanjung Priok';
			$dest_label   = $shipper_name !== '' ? $shipper_name : 'Alamat Shipper';
		}

		$distance_km = $this->m_inv_to_shipper->call_road_distance_api($origin_lat, $origin_lng, $dest_lat, $dest_lng, $profile);
		log_message('error', '[CO2_DEBUG][trucking] profile='.$profile.' hasil call_road_distance_api() = '.var_export($distance_km, true));

		if (!$distance_km) {
			log_message('error', '[CO2_DEBUG][trucking] STOP: API jarak darat gagal/null -> total_kg = null');
			return null;
		}

		// Sanity-check: bandingkan dengan jarak garis-lurus (haversine). Kalau
		// hasil API jauh lebih besar dari yang wajar, kemungkinan rute ORS
		// "nyasar" (pernah terjadi untuk driving-hgv rute Priok-Karawang) ->
		// buang hasilnya daripada menampilkan angka yang salah.
		$straight_km = $this->haversine_km($origin_lat, $origin_lng, $dest_lat, $dest_lng);
		$detour_multiplier = $is_city_to_city ? 8 : 5;
		$max_reasonable_km = max($straight_km * $detour_multiplier, $straight_km + 20);
		log_message('error', '[CO2_DEBUG][trucking] straight_km='.$straight_km.' max_reasonable_km='.$max_reasonable_km);

		if ($distance_km > $max_reasonable_km) {
			log_message('error', '[CO2_DEBUG][trucking] STOP: distance_km ('.$distance_km.') jauh melebihi batas wajar ('.$max_reasonable_km.') -> dibuang, total_kg = null');
			return null;
		}

		$total_kg = round($weight_ton * $distance_km * self::CO2_EMISSION_FACTOR_TRUCKING, 1);
		log_message('error', '[CO2_DEBUG][trucking] hasil akhir total_kg = '.$total_kg);

		return array(
			'total_kg'       => $total_kg,
			'weight_ton'     => $weight_ton,
			'distance_km'    => round($distance_km, 1),
			'factor'         => self::CO2_EMISSION_FACTOR_TRUCKING,
			'distance_label' => 'Estimasi rute Darat '.strtoupper($origin_label).' - '.strtoupper($dest_label),
		);
	}

	/**
	 * Jarak garis-lurus (great-circle) antara 2 titik koordinat, dalam km.
	 * Dipakai sebagai sanity-check terhadap hasil API rute darat (ORS) -
	 * bukan untuk perhitungan emisi itu sendiri (yang dipakai tetap jarak
	 * rute riil dari API).
	 */
	private function haversine_km($lat1, $lng1, $lat2, $lng2)
	{
		$earth_radius_km = 6371;

		$d_lat = deg2rad($lat2 - $lat1);
		$d_lng = deg2rad($lng2 - $lng1);

		$a = sin($d_lat / 2) * sin($d_lat / 2)
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lng / 2) * sin($d_lng / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return $earth_radius_km * $c;
	}

	/**
	 * Taruh ikon daun (PNG) di posisi $x,$y dengan lebar $size (mm), tinggi
	 * menyesuaikan proporsi gambar aslinya. Kalau file-nya tidak ketemu,
	 * dilewati saja (tidak bikin invoice gagal cetak) - dicatat di log.
	 *
	 * Path ikon: FCPATH.'public/images/leaf.png' - sesuaikan di sini kalau
	 * lokasinya beda dari struktur folder kamu.
	 */
	private function draw_leaf_icon($pdf, $x, $y, $size)
	{
		$icon_path = FCPATH.'public/images/leaf.png';

		if (!file_exists($icon_path)) {
			log_message('error', '[CO2_DEBUG] file ikon daun tidak ditemukan di: '.$icon_path);
			return;
		}

		$pdf->Image($icon_path, $x, $y, $size);
	}

	/**
	 * Baris "Total Emisi CO2" - ditempatkan di kiri bawah, sejajar dengan
	 * baris total "IDR :" dan tepat di atas "BANK ACCOUNT DETAIL". Area ini
	 * selalu kosong terlepas dari panjang isi SI NUMBER/SUPPLIER (beda
	 * dengan posisi sebelumnya yang bisa numpuk kalau field itu multi-baris).
	 * Tanpa border/background - cuma ikon daun + teks.
	 *
	 * $total_kg null -> baris ini tidak digambar sama sekali.
	 */
	private function draw_co2_badge($pdf, $total_kg)
	{
		if ($total_kg === null) {
			return;
		}

		$val = $this->fungsi->pecah(round($total_kg, 1), ',');

		$this->draw_leaf_icon($pdf, 10, 239, 4);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('verdana', 'B', 7);
		$pdf->SetXY(16, 240.8);
		$pdf->Write(0, 'TOTAL EMISI CO2 : '.$val.' KG');

		$pdf->SetFont('verdana', '', 8);
	}

	/**
	 * Potong string yang terlalu panjang supaya tidak merusak layout tabel
	 * breakdown CO2 (kolom "Keterangan/Sumber" lebarnya terbatas). Pakai
	 * "..." ASCII biasa, BUKAN karakter unicode ellipsis (…) - supaya aman
	 * dari masalah encoding font FPDF.
	 */
	private function truncate_text($s, $max = 48)
	{
		$s = (string)$s;
		if (strlen($s) <= $max) {
			return $s;
		}
		return substr($s, 0, max(0, $max - 3)).'...';
	}

	/**
	 * Tabel breakdown "PERHITUNGAN EMISI CO2" - KHUSUS shipper BUKIT MURIA
	 * JAYA, sesuai permintaan mereka supaya tidak bingung dengan angka
	 * 1-baris badge biasa (format: No/Parameter/Nilai/Satuan/Keterangan-
	 * Sumber, isi Berat Kargo/Jarak Tempuh/Faktor Emisi/Total Emisi CO2).
	 *
	 * Ditaruh di area kosong kiri-bawah yang sama dengan draw_co2_badge()
	 * (mulai y=235.8, tinggi total ~13.8mm sampai y=249.6, SEBELUM baris
	 * "BANK ACCOUNT DETAIL" di y=253) - supaya TIDAK menambah halaman
	 * invoice. Font kecil (5.5-6pt) khusus untuk tabel ini saja.
	 *
	 * Kalau $breakdown tidak punya rincian lengkap (mis. totalnya dari data
	 * tracking, bukan estimasi weight x distance x factor), fallback ke
	 * badge 1-baris biasa karena tidak ada apa-apa untuk di-breakdown.
	 */
	private function draw_co2_breakdown_table($pdf, $breakdown)
	{
		if ($breakdown === null || $breakdown['total_kg'] === null) {
			return;
		}

		if ($breakdown['distance_km'] === null || $breakdown['weight_ton'] === null || $breakdown['factor'] === null) {
			$this->draw_co2_badge($pdf, $breakdown['total_kg']);
			return;
		}

		$weight_ton_str  = $this->fungsi->pecah(round($breakdown['weight_ton'], 2), ',');
		$distance_km_str = $this->fungsi->pecah(round($breakdown['distance_km']), ',');
		$factor_str      = number_format($breakdown['factor'], 3, '.', ',');
		$total_kg_str    = $this->fungsi->pecah(round($breakdown['total_kg'], 1), ',');
		$distance_label  = $this->truncate_text($breakdown['distance_label'] ?? '', 48);

		$x0 = 10;
		$y  = 235.8;
		$row_h = 2.3;
		$w = array(6, 38, 15, 14, 65); // No|Parameter|Nilai|Satuan|Keterangan, total 138mm (fits before totals box at x=151)

		$pdf->SetTextColor(0, 0, 0);

		$pdf->SetFont('verdana', 'B', 6);
		$pdf->SetXY($x0, $y);
		//$pdf->Write(0, 'PERHITUNGAN EMISI CO2 (CO2 EMISSIONS CALCULATION)');
		$y += $row_h;

		$header = array('No', 'Parameter', 'Nilai', 'Satuan', '');
		$pdf->SetFont('verdana', 'B', 5.5);
		$pdf->SetXY($x0, $y);
		foreach ($header as $i => $text) {
			$pdf->Cell($w[$i], $row_h, $text, 0, 0, 'L');
		}
		$y += $row_h;

		$rows = array(
			array('1', 'Berat Kargo (Cargo Weight)', $weight_ton_str, 'ton', ''),
			array('2', 'Jarak Tempuh (Distance)', $distance_km_str, 'km', ''),
			array('3', 'Faktor Emisi (Emission Factor)', $factor_str, 'kg CO2e/ton-km', ''),
		);

		$pdf->SetFont('verdana', '', 6);
		foreach ($rows as $r) {
			$pdf->SetXY($x0, $y);
			foreach ($r as $i => $text) {
				$pdf->Cell($w[$i], $row_h, $text, 0, 0, 'L');
			}
			$y += $row_h;
		}

		$pdf->Line($x0, $y + 0.5, $x0 + 80, $y + 0.5);
		$y += 1.5;

		$pdf->SetFont('verdana', 'B', 7);
		$pdf->SetXY($x0, $y);
		$pdf->Cell($w[0]+$w[1], $row_h, 'Total Emisi CO2 (1 x 2 x 3)', 0, 0, 'L');
		$pdf->Cell($w[2], $row_h, $total_kg_str, 0, 0, 'L');
		$pdf->Cell($w[3], $row_h, 'kg CO2e', 0, 0, 'L');

		$pdf->SetFont('verdana', '', 8);
	}

	/**
	 * Wrapper: tampilkan badge/breakdown CO2 kalau salah satu dari:
	 * 1) $kode_ref mengandung '-1' atau '-3' (aturan awal, jalur ocean freight).
	 * 2) job == '400' DAN ada item "TRUCKING" di detail charges (jalur
	 *    trucking darat, pelabuhan -> alamat shipper) - berlaku untuk
	 *    shipper manapun yang sudah punya koordinat terdaftar.
	 * 3) job == '702' atau '703' DAN ada item "TRUCKING" di detail charges
	 *    DAN shipper-nya BUKIT MURIA JAYA (permintaan user 2026-10-01).
	 *    Perhitungannya sama persis dengan job 400 (Tanjung Priok -> alamat
	 *    shipper), tapi untuk SEKARANG sengaja dibatasi hanya shipper ini -
	 *    shipper lain di job 702/703 belum dapat badge CO2 walau ada item
	 *    TRUCKING, sampai ada konfirmasi untuk diperluas lagi.
	 * 4) job == '900'/'901'/'902' (trucking antar-kota) DAN shipper-nya
	 *    BUKIT MURIA JAYA (permintaan user 2026-10-01). Beda dari (2)/(3):
	 *    tidak perlu cek item "TRUCKING" di detail charges (job ini memang
	 *    selalu trucking), dan rute dihitung dari alamat shipper itu
	 *    sendiri ke kota tujuan (bukan dari Tanjung Priok).
	 * Job/kondisi lain tidak menampilkan apa pun terkait CO2.
	 *
	 * Shipper BMJ (ocean freight ATAU trucking manapun di atas) dapat tabel
	 * breakdown lengkap (draw_co2_breakdown_table()); shipper lain tetap
	 * dapat badge 1-baris biasa (draw_co2_badge()), sesuai permintaan BMJ
	 * supaya mereka tidak bingung dengan perhitungannya.
	 *
	 * $data = hasil query detail charges (CI_DB_result), dipakai untuk cek
	 * item trucking. Boleh null kalau caller belum punya datanya.
	 * $shipper_name = nama shipper (string bebas, dari GET 'shipper' - sama
	 * seperti yang dipakai untuk cek GIVAUDAN di atas), dipakai untuk cek
	 * BUKIT MURIA JAYA. Boleh kosong kalau caller tidak mengirim (otomatis
	 * tidak eligible untuk jalur trucking yang mensyaratkan BMJ).
	 */
	private function maybe_draw_co2_badge($pdf, $query, $kode_ref, $data = null, $shipper_name = '')
	{
		$job = substr($kode_ref, 4, 3);

		$eligible_ocean = (strpos($kode_ref, '-1') !== false || strpos($kode_ref, '-3') !== false);

		// Job air freight (200/600) - sama sekali tidak kena rule ocean/trucking
		// di atas (tidak ada "-1"/"-3" di kode_ref-nya, dan bukan job trucking),
		// jadi sebelumnya CO2 tidak pernah muncul untuk job ini. Sekarang punya
		// jalur sendiri lewat get_total_co2_kg_air() (cache tracking AWB).
		$eligible_air = ($job === '200' || $job === '600');

		$is_bmj = (stripos((string)$shipper_name, 'BUKIT MURIA JAYA') !== false);

		$is_item_checked_trucking_job = ($job === '400' || $job === '702' || $job === '703');
		$is_city_trucking_job         = ($job === '900' || $job === '901' || $job === '902');

		$has_trucking_item = false;
		if ($is_item_checked_trucking_job && $data) {
			foreach ($data->result_array() as $row) {
				if (stripos($row['item'] ?? '', 'trucking') !== false) {
					$has_trucking_item = true;
					break;
				}
			}
		}

		$eligible_trucking_400 = ($job === '400' && $has_trucking_item);
		$eligible_trucking_70x = (($job === '702' || $job === '703') && $has_trucking_item && $is_bmj);
		$eligible_trucking_90x = ($is_city_trucking_job && $is_bmj);
		$eligible_trucking = $eligible_trucking_400 || $eligible_trucking_70x || $eligible_trucking_90x;

		log_message('error', '[CO2_DEBUG] kode_ref="'.$kode_ref.'" job='.$job.' shipper_name="'.$shipper_name.'" eligible_ocean='.var_export($eligible_ocean, true).' eligible_air='.var_export($eligible_air, true).' eligible_trucking='.var_export($eligible_trucking, true).' (trucking_400='.var_export($eligible_trucking_400, true).' trucking_70x='.var_export($eligible_trucking_70x, true).' trucking_90x='.var_export($eligible_trucking_90x, true).' is_bmj='.var_export($is_bmj, true).')');

		if (!$eligible_ocean && !$eligible_air && !$eligible_trucking) {
			log_message('error', '[CO2_DEBUG] tidak memenuhi syarat manapun -> badge dilewati');
			return;
		}

		if ($eligible_air) {
			$breakdown = $this->get_total_co2_kg_air($query, $kode_ref);
		} else {
			$breakdown = $this->get_total_co2_kg($query, $kode_ref, $eligible_trucking, $shipper_name);
		}
		log_message('error', '[CO2_DEBUG] breakdown final yang dipakai untuk draw = '.var_export($breakdown, true));

		if ($breakdown === null) {
			return;
		}

		// hanya BMJ yang dapat tabel breakdown detail - shipper lain tetap dapat
		// badge 1-baris seperti biasa (rule lama), BUKAN dilewati sama sekali.
		if ($is_bmj) {
			$this->draw_co2_breakdown_table($pdf, $breakdown);
		} else {
			$this->draw_co2_badge($pdf, $breakdown['total_kg']);
		}
	}


	/**
	 * Baca pilihan MAWB/HAWB dari querystring (?awb_type=mawb|hawb), dikirim
	 * dari radio button di editor invoice/proforma untuk job AF (200/600).
	 * Default 'mawb' kalau parameter kosong/tidak dikenal.
	 */
	private function get_awb_type_from_get()
	{
		$t = strtolower(trim((string)$this->input->get('awb_type')));
		return ($t === 'hawb') ? 'hawb' : 'mawb';
	}

	function get_table_tiga()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
	 	
	 	if(!$sidx) $sidx = 1;
		$q = $this->m_inv_to_shipper->get_table_tiga($_POST,false);
		$query = $this->m_inv_to_shipper->get_table_tiga($_POST,true);
		
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
			 $data->rows[$i]['cell']=array(
												$row->number,
												strtoupper($row->code),
												strtoupper($row->item),
												$row->qty,
												$row->currency,
												$row->unit_price,
												$row->price,
												$row->remarks,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data()
	{
 		$result = $this->m_inv_to_shipper->manage_data($_POST);
		
		// [PERBAIKAN] Cek apakah result berupa array (dari logic baru)
		if(is_array($result)) {
			// Merge dengan oper agar JS tetap menerima parameter 'oper'
			$response = array_merge($result, array('oper'=>$_POST['oper']));
			echo json_encode($response);
		} else {
			// Fallback logic lama (jika result cuma angka 1/0)
			echo json_encode(array('status'=>$result, 'oper'=>$_POST['oper']));
		}
	}
	
	function invoice() {
		//$query = $this->m_inv_to_shipper->get_report_pdf($this->input->get('kode_ref'));
		//print_r($this->dbx->last_query());
		//die();
		
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
		header('Content-Disposition: inline; filename="'.$this->input->get('no_invoice').'";');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

		$no_invoice = trim((string)$this->input->get('no_invoice'));
		$invoice_date = (string)$this->input->get('invoice_date');
		$currency = (string)$this->input->get('currency');
		$kepada = (string)$this->input->get('kepada');
		$kepada_pajak = (string)$this->input->get('kepada_pajak');
		$jenis_inv = (string)$this->input->get('jenis');
		$shipper = (string)$this->input->get('shipper');
		$pos = strpos($shipper, 'GIVAUDAN');
		$region = trim((string)$this->session->userdata('region_id'));
		$numb = substr($no_invoice,0,3);
		$kode_ref = trim((string)$this->input->get('kode_ref'));
		$id = (int)$this->input->get('id');
		$pph = (string)$this->input->get('pph23');
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		$query = $this->m_inv_to_shipper->get_report_pdf($this->input->get('kode_ref'));
		if($pph== 'yes'){
		$data =  $this->m_inv_to_shipper->get_form_inv_pph($this->input->get('id'));
		}else{
		$data =  $this->m_inv_to_shipper->get_form_inv($this->input->get('id'));
		//print_r($this->dbx->last_query());
		//die();
		}
		if (strtoupper($query['shipment_type_multi'])=='COLOAD'){
			if($region=='Jakarta')
			{$shipment='COLOAD';}
			else
			{$shipment='LCL';}
		}else{
		$shipment = strtoupper($query['shipment_type_multi']);
		}
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AliasNbPages();
		$pdf->AddPage('P','invoice');
		$pdf->SetAutoPageBreak(true,10);
		

		if ($region=='Jakarta'){
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		else if ($region=='GCL WAREHOUSE')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('KAWASAN INDUSTRI DAN PERGUDANGAN'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MARUNDA CENTER BLOK B-27',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BEKASI, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (021) 89088309',0,1,'R',false,'');
		}
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(148, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(137, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(149, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166.5, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170.5, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(162, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Makassar')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. DR. WAHIDIN SUDIRO HUSODO NO.278'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MALIMONGAN, KEC. WAJO',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA MAKASSAR, SULAWESI SELATAN 90163',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE :  ( 0411 ) 878658',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtupg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);
		   
		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,' REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			if($jenis_inv=='1'){
			$pdf->write(0,' INVOICE ');
			}else{
			$pdf->write(0,' INVOICE ');	
			}
		}
			$pdf->SetFont('verdana','',10);
			$pdf->SetXY(170, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false);
		
			$pdf->Line(10,53,200,53);		
			$pdf->Line(120,110,120,53);
		
			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		if($jenis_inv=='3' && $pos === false){
		$pdf->MultiCell(105,3,$kepada_pajak);
		}else{
		$pdf->MultiCell(105,3,$kepada);
		}
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref);
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'TERM');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['incoterms']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['no_pack_qty'].' '.strtoupper($query['no_pack_unit']));
		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$job = substr($kode_ref,4,3);
		// Job 200 (AF export) / 600 (AF import) = mode Air Freight: label VESSEL jadi FLIGHT,
		// dan B/L NO+MBL NO diganti 1 baris HAWB atau MAWB (sesuai radio pilihan di editor).
		$is_af_job = ($job=='200'||$job=='600');

		$pdf->SetXY(10, 83);
		$pdf->write(0,$is_af_job ? 'FLIGHT' : 'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(29, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902') 
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		} 
		else 
		{
			if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			$pdf->write(0,$vessel??$query['vessel'].' '.$query['voyage']);
		}
		
		////print_r(array($job,$query['freight'],$query['vessel']));exit();

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));
		
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);
		$awb_type = $this->get_awb_type_from_get();

		if ($is_af_job) {
		if($job=='600')   {
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['port_dest']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		else{
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['port_loading']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		} else {
		$pdf->SetXY(10, 91);
			$pdf->write(0,'POL/POD');
			$pdf->SetXY(27, 91);
			$pdf->write(0,':');
			$pdf->SetXY(29, 91);
			if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='900'||$job=='901'||$job=='902')   {
			$pdf->write(0,$query['destination'].' / '.$query['port_dest']);}
			else{
			$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
			$pdf->SetXY(10, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);

		$pdf->write(0,strtoupper($query['hb_l']));

		$pdf->SetXY(10, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$query['mother_bl']);
		}
		
		$pdf->Line(10,103,200,103);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')  {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);
		//$pdf->Line(10,53,10,103);
		//$pdf->Line(200,53,200,103);
		
		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = round($row['price']);
				$codes = array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2');
				if (preg_match('/' . implode('|', array_map('preg_quote', $codes)) . '/', $row['code'])) {
					$item = 'VAT';
				}else{
					$item = $row['item'];
				}
				$pdf->Cell(80, 6,strtoupper($item),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'L');
				$pdf->Cell(21, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
				$j++;
				if ($j>20)
				{ $pdf->AddPage();
			   $j=1;
				}
			}
			
		} 
		
		if ($pph == 'yes'){
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv_pph($id,'RP.');
		$jumlah_pph = round(array_sum($jumlah_idr) * 0.02);
		$jumlah_tax = $this->m_inv_to_shipper->get_ttl_tax_pph($id,'RP.');
		$jumlah_ttl = round(array_sum($jumlah_idr)) - $jumlah_pph + round(array_sum($jumlah_tax));
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		$code_pajak = $this->m_inv_to_shipper->get_code_pajak($id);
		
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		$ttl_tax=$this->fungsi->pecah(round(array_sum($jumlah_tax)),',');
		$ttl_pph=$this->fungsi->pecah(round($jumlah_pph),',');
		$ttl_titak=$this->fungsi->pecah(round($jumlah_ttl),',');
		// Set positions and add borders for each line
		$lineHeight = 8;

		// Sub Total
		$pdf->Line(10,213,200,213);
		$pdf->SetXY(120, 213);
		$pdf->Cell(40, $lineHeight, 'Sub Total:', 'B', 0, 'L');  // Label with border
		$pdf->Cell(40, $lineHeight, $ttl_idr, 'B', 1, 'R');  // Value with border

		// PPh23
		$pdf->SetXY(120, 213 + $lineHeight);
		$pdf->Cell(40, $lineHeight, 'PPh23 (2%):', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_pph, 'B', 1, 'R');

		// PPN
		$pdf->SetXY(120, 213 + 2 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'VAT', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_tax, 'B', 1, 'R');

		// Total (bold for emphasis)
		$pdf->SetFont('verdana', 'B', 10);
		$pdf->SetXY(120, 213 + 3 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'Total:', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_titak, 'B', 1, 'R');
		$pdf->SetFont('verdana','',8);
		}else{
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		$pdf->Line(10,235,200,235);
		$pdf->Line(151,245,151,235);
		$pdf->Line(200,245,151,245);
		$pdf->Line(200,280,135,280);
		if($ttl_usd!='') {
			$pdf->SetXY(153, 236);
			$pdf->MultiCell(12,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 236);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 240);
			$pdf->MultiCell(12,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 240);
			//$pdf->MultiCell(38,4,$this->fungsi->pecah($ttl_idr,','),0,'R',false);
			$pdf->MultiCell(38,4, $ttl_idr,0,'R',false);
		}
		}
		
		if($region =='Osline') {
		$pdf->SetXY(145, 250);
		$pdf->write(0,$company);
		$pdf->SetXY(155, 255);
		$pdf->write(0,'AS AGENT OSLINE');
		
		}else{
			
		$pdf->SetXY(145, 250);
		$pdf->write(0,$company);	
		}		
			$this->maybe_draw_co2_badge($pdf, $query, $kode_ref, $data, $shipper);
			$pdf->SetFont('verdana','',8);
			$offset = 10;

			$pdf->SetXY(10, 253 + $offset);
			$pdf->write(0,'BANK ACCOUNT DETAIL :');

			$pdf->SetXY(10, 257 + $offset);
			$pdf->write(0,'IDR :');

			$pdf->SetXY(20, 257 + $offset);
			$pdf->write(0,'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(20, 261 + $offset);
			$pdf->write(0,'MAYBANK INDONESIA' );

			$pdf->SetXY(20, 265 + $offset);
			$pdf->write(0,'A/C NUMBER : 2082409727');

			$pdf->SetXY(148,277);
			$pdf->SetFont('verdana','',6);
			$pdf->write(0,'Created by : '.$this->session->userdata('fullname'));

		ob_clean();
		$pdf->Output($no_invoice.'.pdf','I'); 
	}

	function e_invoice() {
	
		
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		
		//QRcode::png($this->input->get('kode_ref'),"test.png");
		
		$no_invoice = trim((string)$this->input->get('no_invoice'));
		$invoice_date = (string)$this->input->get('invoice_date');
		$currency = (string)$this->input->get('currency');
		$kepada = (string)$this->input->get('kepada');
		$kepada_pajak = (string)$this->input->get('kepada_pajak');
		$shipper = (string)$this->input->get('shipper');
		$pos = strpos($shipper, 'GIVAUDAN');
		$jenis_inv = (string)$this->input->get('jenis');
		$region = trim((string)$this->session->userdata('region_id'));
		$numb = substr($no_invoice,0,3);
		$kode_ref = trim((string)$this->input->get('kode_ref'));
		$id = (int)$this->input->get('id');
		$pph = (string)$this->input->get('pph23');
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		$query = $this->m_inv_to_shipper->get_report_pdf($this->input->get('kode_ref'));
		if($pph== 'yes'){
		$data =  $this->m_inv_to_shipper->get_form_inv_pph($this->input->get('id'));
		}else{
		$data =  $this->m_inv_to_shipper->get_form_inv($this->input->get('id'));
		}
		if (strtoupper($query['shipment_type_multi'])=='COLOAD'){
			if($region=='Jakarta')
			{$shipment='COLOAD';}
			else
			{$shipment='LCL';}
		}else{
		$shipment = strtoupper($query['shipment_type_multi']);
		}
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AliasNbPages();	
		$pdf->setDataHeader(array('report'=>'e_invoice'));
		$pdf->AddPage('P','invoice');

		if ($region=='Jakarta'){
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');;
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		} 
			else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(148, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(137, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(149, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166.5, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170.5, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(162, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Makassar')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. DR. WAHIDIN SUDIRO HUSODO NO.278'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MALIMONGAN, KEC. WAJO',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA MAKASSAR, SULAWESI SELATAN 90163',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE :  ( 0411 ) 878658',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtupg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);
		   
		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,' REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			if($jenis_inv=='1'){
			$pdf->write(0,' INVOICE ');
			}else{
			$pdf->write(0,' INVOICE ');	
			}
		}
			$pdf->SetFont('verdana','',10);
			$pdf->SetXY(170, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false);
		
			$pdf->Line(10,53,200,53);		
			$pdf->Line(120,110,120,53);
		
			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		if($jenis_inv=='3' && $pos === false){
		$pdf->MultiCell(105,3,$kepada_pajak);
		}else{
		$pdf->MultiCell(105,3,$kepada);
		}
		
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref);
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'TERMS');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['incoterms']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['no_pack_qty'].' '.strtoupper($query['no_pack_unit']));
		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$job = substr($kode_ref,4,3);
		// Job 200 (AF export) / 600 (AF import) = mode Air Freight: label VESSEL jadi FLIGHT,
		// dan B/L NO+MBL NO diganti 1 baris HAWB atau MAWB (sesuai radio pilihan di editor).
		$is_af_job = ($job=='200'||$job=='600');

		$pdf->SetXY(10, 83);
		$pdf->write(0,$is_af_job ? 'FLIGHT' : 'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(29, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902') 
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		} 
		else 
		{
			if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			$pdf->write(0,$vessel??$query['vessel'].' '.$query['voyage']);
		}
		
		////print_r(array($job,$query['freight'],$query['vessel']));exit();

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));
		
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);
		$awb_type = $this->get_awb_type_from_get();

		if ($is_af_job) {
		if($job=='600')   {
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['port_dest']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		else{
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['port_loading']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		} else {
		$pdf->SetXY(10, 91);
			$pdf->write(0,'POL/POD');
			$pdf->SetXY(27, 91);
			$pdf->write(0,':');
			$pdf->SetXY(29, 91);
			if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='900'||$job=='901'||$job=='902')   {
			$pdf->write(0,$query['destination'].' / '.$query['port_dest']);}
			else{
			$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
			$pdf->SetXY(10, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);

		$pdf->write(0,strtoupper($query['hb_l']));

		$pdf->SetXY(10, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$query['mother_bl']);
		}
		
		$pdf->Line(10,103,200,103);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')   {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);
		//$pdf->Line(10,53,10,103);
		//$pdf->Line(200,53,200,103);
		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = round($row['price']);
				$codes = array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2');
				if (preg_match('/' . implode('|', array_map('preg_quote', $codes)) . '/', $row['code'])) {
					$item = 'VAT';
				}else{
					$item = $row['item'];
				}
				$pdf->Cell(80, 6,strtoupper($item),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'L');
				$pdf->Cell(21, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
				$j++;
			}
			if ($j >20)
			{ $pdf->AddPage();}
		} 
		
		if ($pph == 'yes'){
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv_pph($id,'RP.');
		$jumlah_pph = round(array_sum($jumlah_idr) * 0.02);
		$jumlah_tax = $this->m_inv_to_shipper->get_ttl_tax_pph($id,'RP.');
		$jumlah_ttl = round(array_sum($jumlah_idr)) - $jumlah_pph + round(array_sum($jumlah_tax));
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		$code_pajak = $this->m_inv_to_shipper->get_code_pajak($id);
		
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		$ttl_tax=$this->fungsi->pecah(round(array_sum($jumlah_tax)),',');
		$ttl_pph=$this->fungsi->pecah(round($jumlah_pph),',');
		$ttl_titak=$this->fungsi->pecah(round($jumlah_ttl),',');
		// Set positions and add borders for each line
		$lineHeight = 8;

		// Sub Total
		$pdf->Line(10,213,200,213);
		$pdf->SetXY(120, 213);
		$pdf->Cell(40, $lineHeight, 'Sub Total:', 'B', 0, 'L');  // Label with border
		$pdf->Cell(40, $lineHeight, $ttl_idr, 'B', 1, 'R');  // Value with border

		// PPh23
		$pdf->SetXY(120, 213 + $lineHeight);
		$pdf->Cell(40, $lineHeight, 'PPh23 (2%):', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_pph, 'B', 1, 'R');

		// PPN
		$pdf->SetXY(120, 213 + 2 * $lineHeight);
		$pdf->Cell(40, $lineHeight, $code_pajak, 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_tax, 'B', 1, 'R');

		// Total (bold for emphasis)
		$pdf->SetFont('verdana', 'B', 10);
		$pdf->SetXY(120, 213 + 3 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'Total:', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_titak, 'B', 1, 'R');
		$pdf->SetFont('verdana','',8);
		$pdf->Image("http://localhost/pdf/qr_generator.php?code=$kode_ref", 160, 246, 28, 28, "png");
		}else{
		
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		
		$pdf->Line(10,235,200,235);
		$pdf->Line(151,245,151,235);
		$pdf->Line(200,245,151,245);
		
		$pdf->Image("http://localhost/pdf/qr_generator.php?code=$kode_ref", 160, 246, 28, 28, "png");
			
		
		if($ttl_usd!='') {
			$pdf->SetXY(153, 236);
			$pdf->MultiCell(12,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 236);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 240);
			$pdf->MultiCell(12,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 240);
			$pdf->MultiCell(38,4,$ttl_idr,0,'R',false);
		}
		}
			$this->maybe_draw_co2_badge($pdf, $query, $kode_ref, $data, $shipper);
			$pdf->SetFont('verdana','',8);
			$offset = 3;

			$pdf->SetXY(10, 253 + $offset);
			$pdf->write(0,'BANK ACCOUNT DETAIL :');

			$pdf->SetXY(10, 257 + $offset);
			$pdf->write(0,'IDR :');

			$pdf->SetXY(20, 257 + $offset);
			$pdf->write(0,'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(20, 261 + $offset);
			$pdf->write(0,'MAYBANK INDONESIA' );

			$pdf->SetXY(20, 265 + $offset);
			$pdf->write(0,'A/C NUMBER : 2082409727');

		ob_clean();
		$pdf->Output($no_invoice.'.pdf','I'); 
	}
	
	
	function e_invoice_kredit() {
	
		
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		
		//QRcode::png($this->input->get('kode_ref'),"test.png");
		
		$no_invoice = trim((string)$this->input->get('no_invoice'));
		$invoice_date = (string)$this->input->get('invoice_date');
		$currency = (string)$this->input->get('currency');
		$kepada = (string)$this->input->get('kepada');
		$kepada_pajak = (string)$this->input->get('kepada_pajak');
		$shipper = (string)$this->input->get('shipper');
		$pos = strpos($shipper, 'GIVAUDAN');
		$jenis_inv = (string)$this->input->get('jenis');
		$region = trim((string)$this->session->userdata('region_id'));
		$numb = substr($no_invoice,0,3);
		$kode_ref = trim((string)$this->input->get('kode_ref'));
		$id = (int)$this->input->get('id');
		$pph = (string)$this->input->get('pph23');
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		$query = $this->m_inv_to_shipper->get_report_pdf($this->input->get('kode_ref'));
		if($pph== 'yes'){
		$data =  $this->m_inv_to_shipper->get_form_inv_pph($this->input->get('id'));
		}else{
		$data =  $this->m_inv_to_shipper->get_form_inv($this->input->get('id'));
		}
		if (strtoupper($query['shipment_type_multi'])=='COLOAD'){
			if($region=='Jakarta')
			{$shipment='COLOAD';}
			else
			{$shipment='LCL';}
		}else{
		$shipment = strtoupper($query['shipment_type_multi']);
		}
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AliasNbPages();	
		$pdf->setDataHeader(array('report'=>'e_invoice_kredit'));
		$pdf->AddPage('P','invoice');

		if ($region=='Jakarta'){
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		} 
			else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(148, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(137, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(149, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166.5, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170.5, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(162, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Makassar')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. DR. WAHIDIN SUDIRO HUSODO NO.278'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MALIMONGAN, KEC. WAJO',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA MAKASSAR, SULAWESI SELATAN 90163',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 0411 ) 878658 ',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtupg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}

		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);
		   
		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,' REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			if($jenis_inv=='1'){
			$pdf->write(0,' INVOICE ');
			}else{
			$pdf->write(0,' INVOICE ');	
			}
		}
			$pdf->SetFont('verdana','',10);
			$pdf->SetXY(170, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false);
		
			$pdf->Line(10,53,200,53);		
			$pdf->Line(120,110,120,53);
		
			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		if($jenis_inv=='3' && $pos === false){
		$pdf->MultiCell(105,3,$kepada_pajak);
		}else{
		$pdf->MultiCell(105,3,$kepada);
		}
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref);
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'TERM');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['incoterms']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['no_pack_qty'].' '.strtoupper($query['no_pack_unit']));
		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$job = substr($kode_ref,4,3);
		// Job 200 (AF export) / 600 (AF import) = mode Air Freight: label VESSEL jadi FLIGHT,
		// dan B/L NO+MBL NO diganti 1 baris HAWB atau MAWB (sesuai radio pilihan di editor).
		$is_af_job = ($job=='200'||$job=='600');

		$pdf->SetXY(10, 83);
		$pdf->write(0,$is_af_job ? 'FLIGHT' : 'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(29, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902') 
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		} 
		else 
		{
			if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			$pdf->write(0,$vessel??$query['vessel'].' '.$query['voyage']);
		}
		
		////print_r(array($job,$query['freight'],$query['vessel']));exit();

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));
		
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);
		$awb_type = $this->get_awb_type_from_get();

		if ($is_af_job) {
		if($job=='600')   {
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['port_dest']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		else{
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['port_loading']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		} else {
		$pdf->SetXY(10, 91);
			$pdf->write(0,'POL/POD');
			$pdf->SetXY(27, 91);
			$pdf->write(0,':');
			$pdf->SetXY(29, 91);
			if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='900'||$job=='901'||$job=='902')   {
			$pdf->write(0,$query['destination'].' / '.$query['port_dest']);}
			else{
			$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
			$pdf->SetXY(10, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);

		$pdf->write(0,strtoupper($query['hb_l']));

		$pdf->SetXY(10, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$query['mother_bl']);
		}
		
		$pdf->Line(10,103,200,103);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')   {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);
		//$pdf->Line(10,53,10,103);
		//$pdf->Line(200,53,200,103);
		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = round($row['price']);
				$codes = array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2');
				if (preg_match('/' . implode('|', array_map('preg_quote', $codes)) . '/', $row['code'])) {
					$item = 'VAT';
				}else{
					$item = $row['item'];
				}
				$pdf->Cell(80, 6,strtoupper($item),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'L');
				$pdf->Cell(21, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
				$j++;
			}
			if ($j >20)
			{ $pdf->AddPage();}
		} 
		
		if ($pph == 'yes'){
			$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv_pph($id,'RP.');
		$jumlah_pph = round(array_sum($jumlah_idr) * 0.02);
		$jumlah_tax = $this->m_inv_to_shipper->get_ttl_tax_pph($id,'RP.');
		$jumlah_ttl = round(array_sum($jumlah_idr)) - $jumlah_pph + round(array_sum($jumlah_tax));
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		$code_pajak = $this->m_inv_to_shipper->get_code_pajak($id);
		
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		$ttl_tax=$this->fungsi->pecah(round(array_sum($jumlah_tax)),',');
		$ttl_pph=$this->fungsi->pecah(round($jumlah_pph),',');
		$ttl_titak=$this->fungsi->pecah(round($jumlah_ttl),',');
		// Set positions and add borders for each line
		$lineHeight = 8;

		// Sub Total
		$pdf->Line(10,213,200,213);
		$pdf->SetXY(120, 213);
		$pdf->Cell(40, $lineHeight, 'Sub Total:', 'B', 0, 'L');  // Label with border
		$pdf->Cell(40, $lineHeight, $ttl_idr, 'B', 1, 'R');  // Value with border

		// PPh23
		$pdf->SetXY(120, 213 + $lineHeight);
		$pdf->Cell(40, $lineHeight, 'PPh23 (2%):', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_pph, 'B', 1, 'R');

		// PPN
		$pdf->SetXY(120, 213 + 2 * $lineHeight);
		$pdf->Cell(40, $lineHeight, $code_pajak, 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_tax, 'B', 1, 'R');

		// Total (bold for emphasis)
		$pdf->SetFont('verdana', 'B', 10);
		$pdf->SetXY(120, 213 + 3 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'Total:', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_titak, 'B', 1, 'R');
		$pdf->SetFont('verdana','',8);
		$pdf->Image("http://localhost/pdf/qr_generator.php?code=$kode_ref", 160, 246, 28, 28, "png");
		}else{
		
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		
		$pdf->Line(10,235,200,235);
		$pdf->Line(151,245,151,235);
		$pdf->Line(200,245,151,245);
		
		$pdf->Image("http://localhost/pdf/qr_generator.php?code=$kode_ref", 160, 246, 28, 28, "png");
			
		
		if($ttl_usd!='') {
			$pdf->SetXY(153, 236);
			$pdf->MultiCell(12,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 236);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 240);
			$pdf->MultiCell(12,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 240);
			$pdf->MultiCell(38,4,$ttl_idr,0,'R',false);
		}
		}
			$this->maybe_draw_co2_badge($pdf, $query, $kode_ref, $data, $shipper);
			$pdf->SetFont('verdana','',8);
			$offset = 3;

			$pdf->SetXY(10, 253 + $offset);
			$pdf->write(0,'BANK ACCOUNT DETAIL :');

			$pdf->SetXY(10, 257 + $offset);
			$pdf->write(0,'IDR :');

			$pdf->SetXY(20, 257 + $offset);
			$pdf->write(0,'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(20, 261 + $offset);
			$pdf->write(0,'MAYBANK INDONESIA' );

			$pdf->SetXY(20, 265 + $offset);
			$pdf->write(0,'A/C NUMBER : 2082409727');

		ob_clean();
		$pdf->Output($no_invoice.'.pdf','I'); 
	}

	
	// =========================================================================
	// INVOICE GABUNGAN (CONSOLIDATED INVOICE)
	// -------------------------------------------------------------------------
	// Permintaan customer (mis. DAIKIN): 1 dokumen tagihan berisi item kena
	// pajak + item non-tax, dengan nomor invoice = nomor Faktur Pajak (INFP).
	//
	// Di sistem invoice TETAP terpisah (INFP + IN/INR):
	//  - faktur pajak / XML Coretax tetap hanya dari invoice INFP,
	//  - posting AR tetap per invoice,
	//  - fungsi ini HANYA membuat PDF, tidak menulis ke database.
	// =========================================================================

	/**
	 * POST finance_id (invoice FP) -> daftar invoice non-tax di job yang sama
	 * yang bisa digabung ke cetakan.
	 */
	function get_combined_invoice_candidates()
	{
		$finance_id = (int)$this->input->post('finance_id', true);
		$res = $this->m_inv_to_shipper->get_combined_invoice_candidates($finance_id);

		if (!$res) {
			return $this->_json_post_response(404, array(
				'status'  => 0,
				'message' => 'INVOICE_NOT_FOUND',
			));
		}

		if (!empty($res['error'])) {
			return $this->_json_post_response(422, array(
				'status'  => 0,
				'message' => $res['error'],
			));
		}

		$list = array();
		foreach ($res['candidates'] as $c) {
			$list[] = array(
				'id'          => (int)$c['id'],
				'number'      => (string)$c['number'],
				'group'       => (string)$c['grp'],
				'status'      => (string)$c['status'],
				'date'        => $this->fungsi->jin_date_str($c['date']),
				'grand_total' => (float)$c['grand_total'],
			);
		}

		return $this->_json_post_response(200, array(
			'status'     => 1,
			'main'       => array(
				'id'     => (int)$res['main']['id'],
				'number' => (string)$res['main']['number'],
			),
			'candidates' => $list,
		));
	}

	/**
	 * PDF invoice gabungan.
	 *
	 * GET:
	 *   id        = x_finance.id invoice Faktur Pajak (nomor yang dicetak)
	 *   with      = id invoice non-tax yang digabung, dipisah koma
	 *   show_ref  = '1' tampilkan nomor invoice non-tax sebagai referensi kecil
	 *   pph23     = 'yes' tampilkan potongan PPh 23 (2% x DPP bagian A, IDR)
	 *   invoice_date, kepada, kepada_pajak, shipper, si_shipper, awb_type
	 *               = sama seperti invoice()
	 *
	 * Semua id divalidasi ulang di server: harus 1 job + 1 region dengan
	 * invoice FP, dan bukan invoice FP.
	 */
	function invoice_gabungan()
	{
		$main_id = (int)$this->input->get('id');
		$res = $this->m_inv_to_shipper->get_combined_invoice_candidates($main_id);

		if (!$res) {
			show_error('Invoice tidak ditemukan.', 404);
			return;
		}
		if (!empty($res['error'])) {
			show_error('Invoice gabungan hanya bisa dicetak dari invoice Faktur Pajak (INFP).', 422);
			return;
		}

		$main = $res['main'];

		$allowed = array();
		foreach ($res['candidates'] as $c) {
			$allowed[(int)$c['id']] = $c;
		}

		$others = array();
		foreach (explode(',', (string)$this->input->get('with')) as $wid) {
			$wid = (int)trim($wid);
			if ($wid > 0 && isset($allowed[$wid])) {
				$others[$wid] = $allowed[$wid];
			}
		}

		if (empty($others)) {
			show_error('Pilih minimal 1 invoice non-tax di job yang sama untuk digabung.', 422);
			return;
		}

		$no_invoice   = trim((string)$main['number']);
		$kode_ref     = trim((string)$main['job_no']);
		$region       = trim((string)$this->session->userdata('region_id'));
		$invoice_date = trim((string)$this->input->get('invoice_date'));
		if ($invoice_date === '' && !empty($main['date'])) {
			$ts = strtotime((string)$main['date']);
			$invoice_date = $ts ? date('d-m-Y', $ts) : '';
		}
		$kepada       = (string)$this->input->get('kepada');
		$kepada_pajak = (string)$this->input->get('kepada_pajak');
		$shipper      = (string)$this->input->get('shipper');
		$pos          = strpos($shipper, 'GIVAUDAN');
		$show_ref     = ((string)$this->input->get('show_ref') !== '0');
		$with_pph     = ((string)$this->input->get('pph23') === 'yes');

		// Nama/alamat NPWP kosong -> pakai "Kepada" biasa supaya BILL TO tidak blank.
		$jenis_inv = (trim($kepada_pajak) === '') ? '1' : '3';

		// ---- Data ----------------------------------------------------------
		$query = $this->m_inv_to_shipper->get_report_pdf($kode_ref);

		$part_a = $this->_split_print_items(
			$this->m_inv_to_shipper->get_finance_items_for_print((int)$main['id'])
		);

		$items_b = array();
		$refs_b  = array();
		foreach ($others as $o) {
			$items_b  = array_merge($items_b, $this->m_inv_to_shipper->get_finance_items_for_print((int)$o['id']));
			$refs_b[] = (string)$o['number'];
		}
		$part_b = $this->_split_print_items($items_b);

		// ---- PDF -----------------------------------------------------------
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="'.str_replace('/', '-', $no_invoice).'.pdf";');

		$this->load->library('FPDF_MC_Table');
		$pdf = new FPDF_MC_Table();
		$pdf->AliasNbPages();
		$pdf->AddPage('P', 'invoice');
		$pdf->SetAutoPageBreak(true, 10);

		$this->_draw_invoice_top($pdf, $region, $query, $no_invoice, $kode_ref, $invoice_date, $kepada, $kepada_pajak, $jenis_inv, $pos);

		// ---- Grand total & batas area item ---------------------------------
		// Grand total dicetak di posisi TETAP di bawah halaman, sama seperti
		// invoice biasa (box USD/IDR di y=235, atau blok PPh23 di y=213).
		// Item/section harus berhenti di atas area itu; kalau lewat -> halaman baru.
		$total_a = $this->_sum_cur($part_a['sub'], $part_a['vat']);
		$total_b = $this->_sum_cur($part_b['sub'], $part_b['vat']);
		$grand   = $this->_sum_cur($total_a, $total_b);
		$has_usd = isset($grand['USD']) && round($grand['USD'], 2) != 0;

		if ($with_pph) {
			$totals_top = $has_usd ? 205 : 213;
		} else {
			$totals_top = 235;
		}
		$this->combined_flow_limit = $totals_top - 2;

		$pdf->SetFont('verdana', '', 8);
		$pdf->SetXY(10, 111);

		// ---- A. Item kena pajak (invoice FP) ---------------------------------
		$this->_combined_section_title($pdf, 'A. TAXABLE SERVICES (SUBJECT TO VAT)',
			'Faktur Pajak ref. : '.$no_invoice);
		$this->_combined_item_rows($pdf, $part_a['lines']);

		$this->_combined_amount_row($pdf, 'Subtotal (DPP)', $part_a['sub'], false, true);
		if (!empty($part_a['vat'])) {
			$this->_combined_amount_row($pdf, $part_a['vat_label'], $part_a['vat']);
		}
		$this->_combined_amount_row($pdf, 'Total A', $total_a, true);
		$pdf->Ln(2);

		// ---- B. Item non-tax (invoice IN/INR) --------------------------------
		$this->_combined_section_title($pdf, 'B. NON-TAXABLE CHARGES / REIMBURSEMENT',
			$show_ref ? 'Ref. : '.implode(', ', $refs_b) : '');
		$this->_combined_item_rows($pdf, $part_b['lines']);

		if (!empty($part_b['vat'])) {
			// Normalnya tidak ada; kalau ada VAT di invoice non-FP tetap ditampilkan apa adanya.
			$this->_combined_amount_row($pdf, 'Subtotal', $part_b['sub'], false, true);
			$this->_combined_amount_row($pdf, $part_b['vat_label'], $part_b['vat']);
			$this->_combined_amount_row($pdf, 'Total B', $total_b, true);
		} else {
			$this->_combined_amount_row($pdf, 'Total B', $total_b, true, true);
		}

		// Catatan pajak (kecil). Tanpa PPh23: tepat di bawah Total B.
		// Dengan PPh23: di kiri blok total (area itu kosong), supaya tidak
		// memakan ruang item.
		$tax_note = "Faktur Pajak (e-Faktur) ".$no_invoice." diterbitkan hanya atas Bagian A. ".
			"Bagian B adalah biaya non-objek PPN / reimbursement dan tidak termasuk DPP.\n".
			"Tax invoice covers Section A only; Section B is not subject to VAT.";

		if (!$with_pph) {
			$this->_combined_ensure_space($pdf, 12);
			$pdf->Ln(2);
			$pdf->SetFont('verdana', '', 6.5);
			$pdf->SetX(10);
			$pdf->MultiCell(140, 3, $tax_note, 0, 'L');
			$pdf->SetFont('verdana', '', 8);
		}

		if ($pdf->GetY() > $this->combined_flow_limit) {
			$pdf->AddPage();
		}

		$this->_combined_bottom_totals($pdf, $grand, $with_pph, $part_a, $part_b, $totals_top);

		if ($with_pph) {
			$pdf->SetFont('verdana', '', 6.5);
			$pdf->SetXY(10, $totals_top + 3);
			$pdf->MultiCell(105, 3, $tax_note, 0, 'L');
			$pdf->SetFont('verdana', '', 8);
		}

		// ---- Footer: CO2 (jika eligible), tanda tangan, bank -----------------

		$data_main = $this->m_inv_to_shipper->get_form_inv((int)$main['id']);
		$this->maybe_draw_co2_badge($pdf, $query, $kode_ref, $data_main, $shipper);

		$pdf->SetFont('verdana', '', 8);
		$pdf->SetXY(145, 250);
		$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');
		if ($region == 'Osline') {
			$pdf->SetXY(155, 255);
			$pdf->write(0, 'AS AGENT OSLINE');
		}

		$offset = 10;
		$pdf->SetXY(10, 253 + $offset);
		$pdf->write(0, 'BANK ACCOUNT DETAIL :');
		$pdf->SetXY(10, 257 + $offset);
		$pdf->write(0, 'IDR :');
		$pdf->SetXY(20, 257 + $offset);
		$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');
		$pdf->SetXY(20, 261 + $offset);
		$pdf->write(0, 'MAYBANK INDONESIA');
		$pdf->SetXY(20, 265 + $offset);
		$pdf->write(0, 'A/C NUMBER : 2082409727');

		$pdf->SetXY(148, 277);
		$pdf->SetFont('verdana', '', 6);
		$pdf->write(0, 'Created by : '.$this->session->userdata('fullname'));

		if (ob_get_length()) {
			ob_clean();
		}
		$pdf->Output(str_replace('/', '-', $no_invoice).'.pdf', 'I');
	}

	/**
	 * Blok grand total di posisi tetap bawah halaman - meniru persis invoice():
	 *  - tanpa PPh23 : garis penuh y=235, box kanan USD/IDR (y=236/240).
	 *  - dengan PPh23: blok Sub Total / PPh23 / VAT / Total mulai y=213
	 *                  (y=205 kalau ada USD, baris Total USD di paling bawah).
	 * PPh23 dihitung 2% x DPP bagian A (IDR) saja - reimbursement bukan objek PPh23.
	 */
	private function _combined_bottom_totals($pdf, array $grand, $with_pph, array $part_a, array $part_b, $top)
	{
		$idr = isset($grand['RP.']) ? $grand['RP.'] : 0;
		$usd = isset($grand['USD']) ? $grand['USD'] : 0;

		$pdf->SetFont('verdana', '', 8);

		if (!$with_pph) {
			$pdf->Line(10, 235, 200, 235);
			$pdf->Line(151, 245, 151, 235);
			$pdf->Line(200, 245, 151, 245);
			$pdf->Line(200, 280, 135, 280);

			if (round($usd, 2) != 0) {
				$pdf->SetXY(153, 236);
				$pdf->MultiCell(12, 4, 'USD : ', 0, 'L', false);
				$pdf->SetXY(160, 236);
				$pdf->MultiCell(38, 4, $this->fungsi->pecah($usd, ','), 0, 'R', false);
			}
			if (round($idr) != 0 || round($usd, 2) == 0) {
				$pdf->SetXY(153, 240);
				$pdf->MultiCell(12, 4, 'IDR : ', 0, 'L', false);
				$pdf->SetXY(160, 240);
				$pdf->MultiCell(38, 4, $this->fungsi->pecah(round($idr), ','), 0, 'R', false);
			}
			return;
		}

		$sub_idr = (isset($part_a['sub']['RP.']) ? $part_a['sub']['RP.'] : 0)
		         + (isset($part_b['sub']['RP.']) ? $part_b['sub']['RP.'] : 0);
		$vat_idr = (isset($part_a['vat']['RP.']) ? $part_a['vat']['RP.'] : 0)
		         + (isset($part_b['vat']['RP.']) ? $part_b['vat']['RP.'] : 0);
		$dpp_a   = isset($part_a['sub']['RP.']) ? $part_a['sub']['RP.'] : 0;
		$pph_idr = round($dpp_a * 0.02);
		$total   = round($sub_idr) - $pph_idr + round($vat_idr);
		$vat_lbl = $part_a['vat_label'] !== '' ? $part_a['vat_label'] : 'VAT';

		$lh = 8;
		$y  = $top;
		$pdf->Line(10, $y, 200, $y);

		$rows = array(
			array('Sub Total:',   $this->fungsi->pecah(round($sub_idr), ','), false),
			array('PPh23 (2%):',  $this->fungsi->pecah($pph_idr, ','),       false),
			array($vat_lbl,       $this->fungsi->pecah(round($vat_idr), ','), false),
			array('Total:',       $this->fungsi->pecah($total, ','),          true),
		);
		if (round($usd, 2) != 0) {
			$rows[] = array('Total USD:', $this->fungsi->pecah($usd, ','), true);
		}

		foreach ($rows as $r) {
			$pdf->SetFont('verdana', $r[2] ? 'B' : '', $r[2] ? 10 : 8);
			$pdf->SetXY(120, $y);
			$pdf->Cell(40, $lh, $r[0], 'B', 0, 'L');
			$pdf->Cell(40, $lh, $r[1], 'B', 1, 'R');
			$y += $lh;
		}
		$pdf->SetFont('verdana', '', 8);
	}

	/**
	 * Pisahkan item jadi baris tagihan biasa vs baris VAT, sekaligus hitung
	 * subtotal per mata uang. Key mata uang: 'RP.' (termasuk kosong) / 'USD'.
	 */
	private function _split_print_items(array $items)
	{
		$lines = array();
		$sub   = array();
		$vat   = array();
		$vat_label = '';

		$labels = array(
			'TAX10'   => 'VAT (PPN 10%)',
			'TAX1'    => 'VAT (PPN 1%)',
			'TAX 11'  => 'VAT (PPN 11%)',
			'TAX 1.1' => 'VAT (PPN 1.1%)',
			'TAX 12'  => 'VAT (PPN 12%)',
			'TAX 1.2' => 'VAT (PPN 1.2%)',
		);

		foreach ($items as $it) {
			$cur = strtoupper(trim((string)($it['currency'] ?? '')));
			$cur = ($cur === 'USD') ? 'USD' : 'RP.';
			$amt = round((float)$it['price']);
			$code = strtoupper(trim((string)($it['code'] ?? '')));

			if ($this->m_inv_to_shipper->is_tax_item_code($code)) {
				$vat[$cur] = ($vat[$cur] ?? 0) + $amt;
				if ($vat_label === '') {
					$vat_label = $labels[$code] ?? 'VAT';
				}
				continue;
			}

			$lines[]   = $it;
			$sub[$cur] = ($sub[$cur] ?? 0) + $amt;
		}

		return array('lines' => $lines, 'sub' => $sub, 'vat' => $vat, 'vat_label' => $vat_label);
	}

	private function _sum_cur(array $a, array $b)
	{
		foreach ($b as $cur => $v) {
			$a[$cur] = ($a[$cur] ?? 0) + $v;
		}
		return $a;
	}

	/** Pindah halaman kalau sisa ruang sebelum area total/footer kurang. */
	/** Batas bawah area item (diisi invoice_gabungan sesuai posisi blok total). */
	private $combined_flow_limit = 228;

	private function _combined_ensure_space($pdf, $h)
	{
		if ($pdf->GetY() + $h > $this->combined_flow_limit) {
			$pdf->AddPage();
			if ($pdf->GetY() < 15) {
				$pdf->SetY(15);
			}
		}
	}

	private function _combined_section_title($pdf, $title, $ref)
	{
		$this->_combined_ensure_space($pdf, 16);
		$pdf->SetX(10);
		$pdf->SetFont('verdana', 'B', 8);
		$pdf->Cell(190, 6, $title, 0, 1, 'L');
		if ($ref !== '') {
			$pdf->SetFont('verdana', '', 6.5);
			$pdf->SetX(10);
			$pdf->Cell(190, 3.5, $ref, 0, 1, 'L');
		}
		$pdf->SetFont('verdana', '', 8);
	}

	/** Baris item, lebar kolom sama persis dengan invoice() (80/30/10/21/13/36). */
	private function _combined_item_rows($pdf, array $lines)
	{
		$pdf->SetFont('verdana', '', 8);
		foreach ($lines as $row) {
			$this->_combined_ensure_space($pdf, 6);
			$pdf->SetX(10);
			$pdf->Cell(80, 6, strtoupper((string)$row['item']), 0, 0, 'L');
			$pdf->Cell(30, 6, $row['qty'], 0, 0, 'C');
			$pdf->Cell(10, 6, $row['currency'], 0, 0, 'L');
			$pdf->Cell(21, 6, $this->fungsi->pecah($row['unit_price'], ','), 0, 0, 'R');
			$pdf->Cell(13, 6, $row['currency'], 0, 0, 'R');
			$pdf->Cell(36, 6, $this->fungsi->pecah(round((float)$row['price']), ','), 0, 0, 'R');
			$pdf->Ln();
		}
	}

	/**
	 * Baris jumlah rata kanan, kolom nominal sejajar kolom AMOUNT item.
	 * 1 baris per mata uang (IDR dulu, lalu USD).
	 */
	private function _combined_amount_row($pdf, $label, array $by_cur, $bold = false, $line_above = false, $h = 5, $size = 8)
	{
		$order = array('RP.' => 'IDR', 'USD' => 'USD');
		$rows  = array();
		foreach ($order as $key => $disp) {
			if (isset($by_cur[$key]) && round($by_cur[$key]) != 0) {
				$rows[] = array($disp, $by_cur[$key]);
			}
		}
		if (empty($rows)) {
			$rows[] = array('IDR', 0);
		}

		$this->_combined_ensure_space($pdf, $h * count($rows) + 1);

		if ($line_above) {
			$y = $pdf->GetY();
			$pdf->Line(10, $y, 200, $y); // garis penuh 1 baris, kiri-kanan halaman
		}

		$pdf->SetFont('verdana', $bold ? 'B' : '', $size);

		$first = true;
		foreach ($rows as $r) {
			$amount = $r[1] < 0
				? '('.$this->fungsi->pecah(abs(round($r[1])), ',').')'
				: $this->fungsi->pecah(round($r[1]), ',');

			$pdf->SetX(10);
			$pdf->Cell(90, $h, '', 0, 0);
			$pdf->Cell(51, $h, $first ? $label : '', 0, 0, 'R');
			$pdf->Cell(13, $h, $r[0], 0, 0, 'R');
			$pdf->Cell(36, $h, $amount, 0, 1, 'R');
			$first = false;
		}

		$pdf->SetFont('verdana', '', 8);
	}

	/**
	 * Kop surat region + judul + blok info shipment + header kolom.
	 * Salinan 1:1 dari bagian atas invoice() supaya tampilan invoice gabungan
	 * identik dengan invoice biasa. (invoice()/e_invoice() sengaja belum
	 * diubah memakai helper ini supaya tidak ada risiko regresi.)
	 */
	private function _draw_invoice_top($pdf, $region, $query, $no_invoice, $kode_ref, $invoice_date, $kepada, $kepada_pajak, $jenis_inv, $pos)
	{
		$logo = 'logo.png';
		$numb = substr($no_invoice, 0, 3);

		if ($region=='Jakarta'){
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		else if ($region=='GCL WAREHOUSE')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('KAWASAN INDUSTRI DAN PERGUDANGAN'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MARUNDA CENTER BLOK B-27',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BEKASI, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (021) 89088309',0,1,'R',false,'');
		}
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(148, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(137, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(149, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166.5, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170.5, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(162, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Makassar')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. DR. WAHIDIN SUDIRO HUSODO NO.278'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MALIMONGAN, KEC. WAJO',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA MAKASSAR, SULAWESI SELATAN 90163',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE :  ( 0411 ) 878658',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtupg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);
		   
		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,' REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			if($jenis_inv=='1'){
			$pdf->write(0,' INVOICE ');
			}else{
			$pdf->write(0,' INVOICE ');	
			}
		}
			$pdf->SetFont('verdana','',10);
			$pdf->SetXY(170, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false);
		
			$pdf->Line(10,53,200,53);		
			$pdf->Line(120,110,120,53);
		
			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		if($jenis_inv=='3' && $pos === false){
		$pdf->MultiCell(105,3,$kepada_pajak);
		}else{
		$pdf->MultiCell(105,3,$kepada);
		}
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref);
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'TERM');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['incoterms']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['no_pack_qty'].' '.strtoupper($query['no_pack_unit']));
		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$job = substr($kode_ref,4,3);
		// Job 200 (AF export) / 600 (AF import) = mode Air Freight: label VESSEL jadi FLIGHT,
		// dan B/L NO+MBL NO diganti 1 baris HAWB atau MAWB (sesuai radio pilihan di editor).
		$is_af_job = ($job=='200'||$job=='600');

		$pdf->SetXY(10, 83);
		$pdf->write(0,$is_af_job ? 'FLIGHT' : 'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(29, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902') 
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		} 
		else 
		{
			if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			$pdf->write(0,$vessel??$query['vessel'].' '.$query['voyage']);
		}
		
		////print_r(array($job,$query['freight'],$query['vessel']));exit();

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));
		
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);
		$awb_type = $this->get_awb_type_from_get();

		if ($is_af_job) {
		if($job=='600')   {
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['port_dest']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		else{
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['port_loading']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		} else {
		$pdf->SetXY(10, 91);
			$pdf->write(0,'POL/POD');
			$pdf->SetXY(27, 91);
			$pdf->write(0,':');
			$pdf->SetXY(29, 91);
			if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='900'||$job=='901'||$job=='902')   {
			$pdf->write(0,$query['destination'].' / '.$query['port_dest']);}
			else{
			$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
			$pdf->SetXY(10, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);

		$pdf->write(0,strtoupper($query['hb_l']));

		$pdf->SetXY(10, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$query['mother_bl']);
		}
		
		$pdf->Line(10,103,200,103);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')  {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);
	}

	function tarik_data_invoice()
	{
		$data = $this->m_inv_to_shipper->tarik_data_invoice($_POST);
		echo json_encode($data);
	}
	
	function npwp()
	{
		$data = $this->user_model->npwp($_POST);
		echo json_encode($data);
	}
	
	function get_id_akhir()
	{
		$data = $this->m_inv_to_shipper->get_id_akhir();
		return $data;
	}

	function manage_data_table()
	{
		$status = $this->m_inv_to_shipper->manage_data_table($_POST);
 		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
	}
	
	function show_excel($id) {	
		$q = $this->m_inv_to_shipper->get_data_pajak($id);
		//print_r($this->dbx->last_query());
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=efaktur.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr>
			<td align="center" width="100" class="header">DATE</td>
			<td align="center" width="120" class="header">CUSTOMER</td>
			<td align="center" width="150" class="header">CODE</td>
			<td align="center" width="150" class="header">ITEM</td>
			<td align="center" width="150" class="header">QTY</td>
			<td align="center" width="150" class="header">PRICE</td>
			<td align="center" width="350" class="header">DISCOUNT</td>
		</tr>';
		$i=1;	
		$n=0;
		foreach($q as $row ) {
			echo 
			'<tr>
				<td align="center">'.$this->fungsi->jin_date_str_strip($row->date).'</td>
				<td align="center">'.$row->kepada.'</td>
				<td>'.$row->code.'</td>
				<td>'.$row->item.'</td>
				<td>'.$row->qty.'</td>
				<td>'.$row->unit_price.'</td>
				<td></td>
			</tr>';
			$i++;$n++;
		}

		echo '</table>';
		exit();
	}
	function excel($id,$no_inv)
		{
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			$sheet->setCellValue('A1', 'DATE');
			$sheet->setCellValue('B1', 'CUSTOMER');
			$sheet->setCellValue('C1', 'CODE');
			$sheet->setCellValue('D1', 'ITEM');
			$sheet->setCellValue('E1', 'QTY');
			$sheet->setCellValue('F1', 'PRICE');
			$sheet->setCellValue('G1', 'DISCOUNT');
			
			$q = $this->m_inv_to_shipper->get_data_pajak($id,$no_inv);
			//print_r($this->dbx->last_query());
			//die();
			$i=1;	
			$n=2;
			foreach($q as $row )
			{
				$sheet->setCellValue('A'.$n,$this->fungsi->jin_date_str_strip($row->date));
				$sheet->setCellValue('B'.$n,$row->shipper_name);
				$sheet->setCellValue('C'.$n,$row->code);
				$sheet->setCellValue('D'.$n,$row->item);
				$sheet->setCellValue('E'.$n,$row->qty);
				$sheet->setCellValue('F'.$n,$row->unit_price);
				$n++;
			}
			$writer = new Xlsx($spreadsheet);
			$filename = 'eFaktur '.rtrim($no_inv, '/');
			ob_end_clean();
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'. $filename .'.xlsx"'); 
			header('Cache-Control: max-age=0');
	
			$writer->save('php://output');
	}
}

/*
getListGrop => prosses add
*/