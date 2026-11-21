<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_job_sheet extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Disarankan menggunakan library REST_Controller jika ada, 
        // namun ini menggunakan CI_Controller standar agar kompatibel dengan kode Anda.
        
        $this->load->model(array(
            'afg_m_cities',
            'afg_m_job_sheet',
            'afg_m_cost',
            'user_model',
            'm_bill_of_ladding',
            'afg_m_stuffing_plan',
            'afg_m_income',
            'reference/type_service_model',
            'report/m_rekap_sales',
        ));
        $this->load->library('Fungsi');

        // Inject database connection sesuai logic Anda
        $this->fungsi->dbx = $this->afg_m_stuffing_plan->dbx;
        $this->dbx = $this->afg_m_stuffing_plan->dbx;

        // Header wajib untuk API JSON
        header('Content-Type: application/json');
    }

    /**
     * GET: Ambil Detail Job Sheet
     * Endpoint: [GET] api_job_sheet/detail?kode_ref=XYZ&id=123
     */
    public function detail()
    {
        $id = $this->input->get('id');
        $kode_ref = $this->input->get('kode_ref');

        if (!$kode_ref) {
            echo json_encode(['status' => false, 'message' => 'Parameter kode_ref dibutuhkan']);
            return;
        }

        $query = $this->afg_m_job_sheet->get_job_form($kode_ref);
        $income_check = $this->afg_m_income->check_data_income($kode_ref, $id);
        $income_list = $this->afg_m_job_sheet->get_income_job_sheet($kode_ref);

        $response = [
            'status' => true,
            'data' => [
                'job_detail' => $query,
                'created_info' => $income_check['created_by'] . ' ' . $income_check['created_at'],
                'income' => $income_list
            ]
        ];

        echo json_encode($response);
    }

    /**
     * POST: Update Data Form
     * Endpoint: [POST] api_job_sheet/update
     */
    public function update()
    {
        $kode_ref = $this->input->post('kode_ref');
        $kurs = $this->input->post('kurs');
        $date = $this->input->post('tanggal');
        $region = $this->input->post('region_id'); // Atau ambil dari session jika login

        if (!$kode_ref || !$kurs) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $result = $this->afg_m_job_sheet->update_data_form($kode_ref, $kurs, $date, $region);
        echo json_encode(['status' => $result, 'message' => $result ? 'Update Berhasil' : 'Update Gagal']);
    }

    /**
     * POST: Manage Data Form (Delete/Edit Mode)
     * Endpoint: [POST] api_job_sheet/manage
     */
    public function manage()
    {
        $id = $this->input->post('id');
        $item = $this->input->post('item');
        $kode_ref = $this->input->post('kode_ref');
        $mode = $this->input->post('mode');
        $region = $this->input->post('region_id');

        $result = $this->afg_m_job_sheet->manage_data_form($id, $item, $kode_ref, $mode, $region);
        echo json_encode(['status' => $result]);
    }

    /**
     * GET: Link ke PDF
     * Karena PDF adalah stream binary, API biasanya hanya memberikan URL-nya saja
     */
    public function get_print_url($tanggal, $kode_ref, $spd, $print, $destination)
    {
        // Mengarahkan client ke fungsi print di controller lama
        $url = base_url("afg_c_job_sheet/print_jobsheet/$tanggal/$kode_ref/$spd/$print/$destination");
        echo json_encode([
            'status' => true,
            'print_url' => $url
        ]);
    }
	
	public function get_jobsheet_json() 
	{
		$tanggal              = $this->input->get('tanggal');
		$kode_ref             = $this->input->get('kode_ref');
		$spd                  = $this->input->get('spd');
		$destination_jobsheet = $this->input->get('destination_jobsheet');

		// 1. Tangkap region dari parameter API, atau gunakan session, atau fallback ke 'JKT' untuk testing Postman
		$region_id = $this->input->get('region_id') ?? $this->session->userdata('region_id') ?? 'JKT';
		
		// Set sementara ke session agar fungsi model yang memanggil session tidak error
		$this->session->set_userdata('region_id', $region_id);

		$this->load->model('afg_m_stuffing_plan');
		
		// 2. Ambil data utama
		$data = $this->afg_m_job_sheet->get_sp_spd_by($kode_ref, $spd);

		// 3. STOP EKSEKUSI jika data tidak ditemukan (Mencegah Efek Domino)
		if (empty($data)) {
			header('Content-Type: application/json');
			echo json_encode([
				'status' => false,
				'message' => 'Data Jobsheet tidak ditemukan untuk kode ref dan spd tersebut di region ' . $region_id
			]);
			return; // Hentikan proses agar tidak meneruskan null ke fungsi lain
		}

		$destination = $data['destination'] ?? '';
		$final_des = $destination_jobsheet;
		
		// Sekarang ini aman karena $destination dipastikan bukan null
		$dest = $this->afg_m_cities->get_city_id($destination); 
		$agent = $this->afg_m_stuffing_plan->get_agent($spd);
			
		$bl_no = $data['hb_l'];
		$imp = substr($kode_ref, 4, 3);
		
		if (in_array($imp, ["300", "301", "302", "400", "600", "800"])) {
			$bl_no = $this->afg_m_job_sheet->get_hb_l($kode_ref);
			$shipper_name = substr($data['consignee'], 0, 25);
			$consignee_name = $data['shipper_import'];
		} else {
			$shipper_name = $data['shipper_print'];
			$consignee_name = ($data['consignee'] !== null) ? $data['consignee'] : '';
		}
		
		$tanggalnya = substr($tanggal, 6, 2) . '/' . substr($tanggal, 4, 2) . '/' . substr($tanggal, 0, 4);
		$detail = $this->afg_m_job_sheet->get_detail_job($spd);
		
		// Vessel Logic
		if (!empty($detail->last_vessel)) {
			$vessel = $detail->last_vessel;
			$vessel_etd_date = $detail->last_vessel_etd_date;
			$vessel_eta_date = $detail->last_vessel_eta_date;
		} else if (!empty($detail->feeder_vessel)) {
			$vessel = $detail->feeder_vessel;
			$vessel_etd_date = $detail->feeder_vessel_etd_date;
			$vessel_eta_date = $detail->feeder_vessel_eta_date;
		} else {
			$vessel = $data['vessel'] . ' ' . $data['voyage'];
			$vessel_etd_date = $data['etd_jkt'];
			$vessel_eta_date = $data['eta'];
		}

		// Conn Vessel Logic
		if ($detail->last_snd_vessel != '') {
			$con_vessel = $detail->last_snd_vessel;
			$con_vessel_etd_date = $detail->last_snd_vessel_etd_date;
			$con_vessel_eta_date = $detail->last_snd_vessel_eta_date;
		} else {
			$con_vessel = $detail->snd_vessel;
			$con_vessel_etd_date = $detail->snd_vessel_etd_date;
			$con_vessel_eta_date = $detail->snd_vessel_eta_date;
		}

		// Container Logic
		$dbl = $this->afg_m_stuffing_plan->get_double($data['sp_id']);
		$containers = [];
		if ($dbl->num_rows() > 0) {
			foreach ($dbl->result() as $row) {
				$containers[] = $row->count_num . ' X ' . $row->count_type;
			}
		} else {
			$containers[] = $data['type_cont_qty'] . ' X ' . $data['type_cont_size'];
		}

		// ---------------- INCOME CALCULATION ----------------
		$income_data = [];
		$iusd = [];
		$irp = [];
		$income = $this->afg_m_job_sheet->get_income_job_sheet($kode_ref);
		foreach ($income as $row) {
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$qty = floatval($row->qty);
			$unit_price = floatval($row->unit_price);
			$total = $unit_price * $qty;
			
			$usd_total = ($row->currency == 'USD') ? $total : 0;
			$rp_total = ($row->currency != 'USD') ? $total : 0;

			$income_data[] = [
				'service' => strtoupper(substr($row->item_service, 0, 25)),
				'code' => $code_service,
				'qty' => $qty,
				'currency' => $row->currency,
				'unit_price' => $unit_price,
				'total_usd' => $usd_total,
				'total_rp' => $rp_total,
				'pc' => substr($row->prep_coll, 0, 1),
				'received' => '',
				'invoice_no' => $row->ship_co ?? ""
			];
			if($row->currency == 'USD') $iusd[] = $usd_total;
			else $irp[] = $rp_total;
		}

		// Global Income
		$gbl_income_data = [];
		$giusd = [];
		$girp = [];
		$gblincome = $this->afg_m_job_sheet->get_income_global($data['code']);	
		foreach ($gblincome as $row) {
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost($kode_ref, 'USD');
			$htn = floatval($qty[0]) / floatval($qty[1]);
			$ttl = floatval($row->price) * $htn;

			$gbl_income_data[] = [
				'service'    => strtoupper(substr("<> " . $row->item_service, 0, 25)),
				'code'       => $code_service,
				'qty_ratio'  => $qty[0] . ' / ' . $qty[1],
				'currency'   => $row->currency,
				'unit_price' => floatval($row->price),        // <--- TAMBAHKAN INI
				'total_usd'  => ($row->currency == 'USD') ? $ttl : 0,
				'total_rp'   => ($row->currency != 'USD') ? $ttl : 0,
				'pc'         => substr($row->prep_coll, 0, 1),
				'payable_to' => $row->payeble_to,
				'no_cn'      => $this->afg_m_job_sheet->get_no_cn([$agent, $row->item_service, $kode_ref])
			];
			if($row->currency == 'USD') $giusd[] = $usd_total;
			else $girp[] = $rp_total;
		}

		$ttl_i_usd = array_sum($iusd) + array_sum($giusd);
		$ttl_i_rp = array_sum($irp) + array_sum($girp);
		$kurs = floatval($data['kurs']);
		$total_global_income = ($kurs * $ttl_i_usd) + $ttl_i_rp;

		// ---------------- COST CALCULATION ----------------
		$cost_data = [];
		$process_costs = function($table, $prefix) use ($kode_ref, $agent, &$cost_data) {
			$costs = $this->afg_m_job_sheet->get_cost_job_sheet($table, $kode_ref);
			foreach ($costs as $row) {
				$code_service = $this->type_service_model->getDataCodeService($row->item_service);
				$price = floatval($row->price);
				if ($table == 'inf_cost_refund_discount') {
					$price = floatval($row->unit_price) * floatval($row->qty);
				}

				$cost_data[$table][] = [
					'service' => substr($prefix . $row->item_service, 0, 25),
					'code' => $code_service,
					'qty' => $row->qty,
					'currency' => $row->currency,
					'unit_price' => floatval($row->unit_price),
					'total_usd' => ($row->currency == 'USD') ? $price : 0,
					'total_rp' => ($row->currency != 'USD') ? $price : 0,
					'pc' => substr($row->prep_coll, 0, 1),
					'payable_to' => strtoupper($row->payable_to ?? ''),
					'no_cn' => $this->afg_m_job_sheet->get_no_cn([$agent, $row->item_service, $kode_ref])
				];
			}
		};

		$process_costs('inf_cost_tbl_satu', '');
		$process_costs('inf_cost_refund_discount', '* ');
		$process_costs('inf_cost_refund_sc', '* ');

		// Global Cost
		$cost_data['x_glbcost'] = [];
		$cusd3 = [];
		$crp3 = [];
		$cost3 = $this->afg_m_job_sheet->get_cost_job_sheet('x_glbcost', $kode_ref);	
		$cost3 = $this->afg_m_job_sheet->get_cost_job_sheet('x_glbcost', $kode_ref);	
			foreach ($cost3 as $row) {
				$code_service = $this->type_service_model->getDataCodeService($row->item_service);
				$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost($kode_ref, 'USD');
				$htn = floatval($qty[0]) / floatval($qty[1]);
				$ttl = floatval($row->price) * $htn;

				$cost_data['x_glbcost'][] = [
					'service'    => strtoupper(substr("<> " . $row->item_service, 0, 25)),
					'code'       => $code_service,
					'qty_ratio'  => $qty[0] . ' / ' . $qty[1],
					'currency'   => $row->currency,               // <--- TAMBAHKAN INI
					'unit_price' => floatval($row->price),        // <--- TAMBAHKAN INI (ambil dari $row->price atau $row->unit_price)
					'total_usd'  => ($row->currency == 'USD') ? $ttl : 0,
					'total_rp'   => ($row->currency != 'USD') ? $ttl : 0,
					'pc'         => substr($row->prep_coll, 0, 1),
					'payable_to' => $row->payeble_to,
					'no_cn'      => $this->afg_m_job_sheet->get_no_cn([$agent, $row->item_service, $kode_ref])
				];
				if($row->currency == 'USD') $cusd3[] = $ttl;
				else $crp3[] = $ttl;
			}

		$ttl_cost = $this->afg_m_stuffing_plan->get_total_cost($kode_ref);
		$total_global_cost = ($kurs * floatval($ttl_cost[0])) + floatval($ttl_cost[1]);
		
		$ttl_dn = $this->afg_m_stuffing_plan->ttl_debit_note($spd); 

		// ---------------- PREPARE JSON RESPONSE ----------------
		$response = [
			'status' => true,
			'header' => [
				'job_no' => $kode_ref,
				'date' => $tanggalnya,
				'customer_type' => in_array($imp, ["300","301","302","400","402","600"]) ? 'CUSTOMER' : 'SHIPPER',
				'shipper_name' => strtoupper($shipper_name ?? ''),
				'consignee' => strtoupper($consignee_name ?? ''),
				'notify' => strtoupper($data['notify_add'] ?? ''),
				'sales_by' => strtoupper($this->fungsi->get_user_by_id($data['sales']) ?? ''),
				'carrier' => $data['carrier'] ?? '',
				'warehouse' => $data['warehouse'] ?? '',
				'trucking' => $this->afg_m_job_sheet->get_trucking($detail->trucking_co ?? ''),
				'desc_of_good' => strtoupper($data['desc_print'] ?? ''),
				'vessel' => [
					'name' => $vessel ?? '',
					'etd'  => $vessel_etd_date ?? '',
					'eta'  => $vessel_eta_date ?? ''
				],
				'conn_vessel' => [
					'name' => strtoupper($con_vessel ?? ''),
					'etd'  => $con_vessel_etd_date ?? '',
					'eta'  => $con_vessel_eta_date ?? ''
				],
				'port_discharge' => $data['destination'] ?? '',
				'port_of_loading' => $data['port_loading'] ?? '',
				'origin_destination_label' => in_array($imp, ["300","301","302","400","402","600"]) ? 'ORIGIN' : 'DESTINATION',
				'origin_destination' => urldecode($final_des ?? ''),
				'container' => implode(', ', $containers ?? []),
				'cont_seal_no' => $data['cont_seal_no'] ?? '',
				'bl_number' => $bl_no ?? '',
				'kurs_rp' => $kurs ?? 0
			],
			'details' => [
				'income' => [
					'items' => $income_data ?? [],
					'global_items' => $gbl_income_data ?? [],
					'summary' => [
						'total_usd' => $ttl_i_usd ?? 0,
						'total_rp' => $ttl_i_rp ?? 0,
						'total_global_idr' => $total_global_income ?? 0
					]
				],
				'cost' => [
					'items' => $cost_data ?? [],
					'summary' => [
						'total_usd' => floatval($ttl_cost[0] ?? 0),
						'total_rp' => floatval($ttl_cost[1] ?? 0),
						'total_global_idr' => $total_global_cost ?? 0
					]
				],
				'due_to' => [
					'no_dn' => $this->afg_m_job_sheet->get_no_dn([$agent, $kode_ref, '']),
					'total_usd' => $ttl_dn ?? 0,
					'total_rp' => ($ttl_dn ?? 0) * ($kurs ?? 0)
				]
			],
			'profit_summary' => [
				"kode_ref"        => $kode_ref,
				"profit"          => ($total_global_income ?? 0) - ($total_global_cost ?? 0) + (($ttl_dn ?? 0) * ($kurs ?? 0)),
				"income"          => ($total_global_income ?? 0) + (($ttl_dn ?? 0) * ($kurs ?? 0)),
				"cost"            => $total_global_cost ?? 0,
				"profit_usd"      => ($ttl_i_usd ?? 0) - ($ttl_cost[0] ?? 0) + ($ttl_dn ?? 0),
				"profit_rp"       => ($ttl_i_rp ?? 0) - ($ttl_cost[1] ?? 0),
				"income_usd"      => $ttl_i_usd ?? 0,
				"income_rp"       => $ttl_i_rp ?? 0,
				"cost_usd"        => $ttl_cost[0] ?? 0,
				"cost_rp"         => $ttl_cost[1] ?? 0,
				"global_cost"     => array_sum($crp3 ?? []),
				"global_cost_usd" => array_sum($cusd3 ?? []),
				"inv_agent"       => $ttl_dn ?? 0,
				"cost_coloader"   => "",
				"shipper_name"    => $data['shipper_print'] ?? '',
				"sales"           => $this->fungsi->get_user_by_id($data['sales']) ?? '',
				"destination"     => $data['destination'] ?? '',
				"volume"          => $data['bl_meas'] ?? '',
				"weight"          => $data['weight'] ?? '',
				"sales_id"        => $data['sales'] ?? ''
			]
		];


		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT);
	}
}