<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        /*
         * Cache:
         * - utama: Redis
         * - fallback: file
         */
        $this->load->driver('cache', [
            'adapter' => 'redis',
            'backup'  => 'file',
        ]);

        $this->load->model('api/finance/Dashboard_model', 'dashboard');
    }

    private function json_ok($data = [], $message = 'OK', $status_code = 200)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode([
                'status'  => true,
                'message' => $message,
                'data'    => $data,
            ]));
    }

    private function json_error($message = 'Error', $status_code = 400, $extra = [])
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode(array_merge([
                'status'  => false,
                'message' => $message,
            ], $extra)));
    }

    private function get_company_id()
    {
        if (method_exists($this, 'auth_company_id')) {
            $company_id = (int) $this->auth_company_id();

            if ($company_id > 0) {
                return $company_id;
            }
        }

        if (isset($this->auth_company_id)) {
            $company_id = (int) $this->auth_company_id;

            if ($company_id > 0) {
                return $company_id;
            }
        }

        $company_id = (int) $this->input->get('company_id');

        return $company_id > 0 ? $company_id : 1;
    }

    private function get_region_id()
    {
        /*
         * region_id di sistem kamu VARCHAR.
         * Jangan cast ke integer.
         */

        $region = trim((string) $this->input->get('region_id'));

        if ($region !== '') {
            return $region;
        }

        foreach (['region', 'region_code', 'company', 'company_code'] as $key) {
            $value = trim((string) $this->input->get($key));

            if ($value !== '') {
                return $value;
            }
        }

        if (isset($this->auth_region)) {
            $auth_region = trim((string) $this->auth_region);

            if ($auth_region !== '') {
                return $auth_region;
            }
        }

        if (method_exists($this, 'auth_region')) {
            $auth_region = trim((string) $this->auth_region());

            if ($auth_region !== '') {
                return $auth_region;
            }
        }

        return '';
    }

    private function get_period()
    {
        /*
         * Default dashboard: YTD
         * Contoh:
         * 2026-01-01 s/d 2026-05-16
         */
        $default_from = date('Y-01-01');
        $default_to   = date('Y-m-d');

        $date_from = trim((string) $this->input->get('date_from'));
        $date_to   = trim((string) $this->input->get('date_to'));

        if ($date_from === '') {
            $date_from = $default_from;
        }

        if ($date_to === '') {
            $date_to = $default_to;
        }

        return [$date_from, $date_to];
    }

    private function cache_key($name, $params = [])
    {
        ksort($params);

        return 'finance_dashboard:' . $name . ':' . md5(json_encode($params));
    }

    private function cache_get($key)
    {
        $cached = $this->cache->get($key);

        if (!$cached) {
            return null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        $decoded = json_decode($cached, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function cache_set($key, $data, $ttl = 60)
    {
        return $this->cache->save($key, json_encode($data), $ttl);
    }

  private function json_cached($cache_name, $params, $callback, $ttl = 60)
	{
		$key = $this->cache_key($cache_name, $params);

		$cached = $this->cache_get($key);

		if ($cached !== null) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(200)
				->set_output(json_encode([
					'status'  => true,
					'message' => 'OK',
					'data'    => $cached,
					'_cache'  => [
						'hit' => true,
						'key' => $key,
					],
				]));
		}

		$data = call_user_func($callback);

		$this->cache_set($key, $data, $ttl);

		return $this->output
			->set_content_type('application/json')
			->set_status_header(200)
			->set_output(json_encode([
				'status'  => true,
				'message' => 'OK',
				'data'    => $data,
				'_cache'  => [
					'hit' => false,
					'key' => $key,
					'ttl' => $ttl,
				],
			]));
	}
  
    public function summary()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();

        list($date_from, $date_to) = $this->get_period();

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
            'date_from'  => $date_from,
            'date_to'    => $date_to,
        ];

        return $this->json_cached('summary', $params, function () use ($params) {
            return $this->dashboard->get_summary($params);
        }, 120);
    }

    public function attention()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
        ];

        return $this->json_cached('attention', $params, function () use ($params) {
            return $this->dashboard->get_attention($params);
        }, 60);
    }

    public function ar_aging()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();

        $as_of = trim((string) $this->input->get('as_of'));

        if ($as_of === '') {
            $as_of = date('Y-m-d');
        }

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
            'as_of'      => $as_of,
        ];

        return $this->json_cached('ar_aging', $params, function () use ($params) {
            return $this->dashboard->get_ar_aging($params);
        }, 120);
    }

    public function ap_aging()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();

        $as_of = trim((string) $this->input->get('as_of'));

        if ($as_of === '') {
            $as_of = date('Y-m-d');
        }

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
            'as_of'      => $as_of,
        ];

        return $this->json_cached('ap_aging', $params, function () use ($params) {
            return $this->dashboard->get_ap_aging($params);
        }, 120);
    }

    public function recent_activity()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();
        $limit      = (int) $this->input->get('limit');

        if ($limit <= 0) {
            $limit = 15;
        }

        if ($limit > 50) {
            $limit = 50;
        }

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
            'limit'      => $limit,
        ];

        return $this->json_cached('recent_activity', $params, function () use ($params) {
            return $this->dashboard->get_recent_activity($params);
        }, 60);
    }

    public function uninvoiced()
    {
        $company_id = $this->get_company_id();
        $region_id  = $this->get_region_id();
        list($date_from, $date_to) = $this->get_period();

        if ($region_id === '') {
            return $this->json_ok([
                'period'                     => ['date_from' => $date_from, 'date_to' => $date_to],
                'total_order_uninvoiced'     => 0,
                'total_count_uninvoiced'     => 0,
                'total_nominal_uninvoiced'   => 0,
                'total_unbilled_idr'          => 0,
                'total_unbilled_usd'          => 0,
                'total_equivalent_idr'        => 0,
                'grouped_by_service'          => [],
                'top_5_shippers'              => [],
                'top_5_shippers_invoiced'     => [],
                'note'                        => 'Region tidak ditemukan dari session/login, data revenue dikosongkan.',
            ], 'OK');
        }

        $params = [
            'company_id' => $company_id,
            'region_id'  => $region_id,
            'date_from'  => $date_from,
            'date_to'    => $date_to,
        ];

        return $this->json_cached('uninvoiced_revenue', $params, function () use ($params) {
            return $this->dashboard->get_uninvoiced_dashboard($params);
        }, 120);
    }

    public function kurs_bi()
    {
        $currency = strtoupper(trim((string) $this->input->get('currency')));
        $date     = trim((string) $this->input->get('date'));

        if ($currency === '') {
            $currency = 'USD';
        }

        if ($date === '') {
            $date = date('Y-m-d');
        }

        $params = [
            'currency' => $currency,
            'date'     => $date,
        ];

        return $this->json_cached('kurs_bi', $params, function () use ($currency, $date) {
            $result = $this->dashboard->fetch_kurs_bi($currency, $date);

            if (!empty($result['status'])) {
                return $result['data'];
            }

            return [
                'currency'       => $currency,
                'date'           => $date,
                'requested_date' => $date,
                'buy_rate'       => 0,
                'sell_rate'      => 0,
                'middle_rate'    => 0,
                'source'         => 'Bank Indonesia',
                'note'           => $result['message'] ?? 'Data kurs BI tidak tersedia.',
                // 👇 TAMBAHKAN BARIS INI
                'debug_raw_text' => $result['debug_raw_text'] ?? 'KOSONG/TIDAK ADA BALASAN DARI BI',
            ];
        }, 3600);
    }

    public function update_kurs_bi()
    {
        $currency = strtoupper(trim((string) $this->input->post('currency')));
        $date     = trim((string) $this->input->post('date'));

        if ($currency === '') {
            $currency = 'USD';
        }

        if ($date === '') {
            $date = date('Y-m-d');
        }

        $result = $this->dashboard->fetch_kurs_bi($currency, $date);

        if (empty($result['status'])) {
            return $this->json_ok([
                'kurs' => [
                    'currency'       => $currency,
                    'date'           => $date,
                    'requested_date' => $date,
                    'buy_rate'       => 0,
                    'sell_rate'      => 0,
                    'middle_rate'    => 0,
                    'source'         => 'Bank Indonesia',
                    'note'           => $result['message'] ?? 'Data kurs BI tidak tersedia.',
                ],
                'saved' => false,
            ], 'Kurs BI tidak tersedia, data tidak disimpan');
        }

        $saved = $this->dashboard->save_kurs_bi_if_table_exists($result['data']);

        $cache_key = $this->cache_key('kurs_bi', [
            'currency' => $currency,
            'date'     => $date,
        ]);

        $this->cache->delete($cache_key);

        return $this->json_ok([
            'kurs'  => $result['data'],
            'saved' => $saved,
        ], $saved ? 'Kurs BI berhasil ditarik dan disimpan' : 'Kurs BI berhasil ditarik, tetapi tabel kurs tidak ditemukan');
    }

    public function clear_cache()
    {
        /*
         * Optional route:
         * $route['api/finance/dashboard/clear-cache']['POST'] = 'api/finance/Dashboard/clear_cache';
         */
        $ok = $this->cache->clean();

        return $this->json_ok([
            'cleared' => (bool) $ok,
        ], $ok ? 'Dashboard cache cleared' : 'Dashboard cache clear failed');
    }
}