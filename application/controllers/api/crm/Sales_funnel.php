<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Sales_funnel extends RestController
{
    private $secret_key = "your-secret-key"; // samakan dengan Login controller

public function __construct()
	{
		parent::__construct();

		// CORS untuk local development
		header('Access-Control-Allow-Origin: http://localhost:5173');
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Credentials: true');

		// Penting: preflight OPTIONS harus langsung 200, jangan lanjut auth/API key
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(200);
			exit;
		}

		$this->load->model('api/crm/M_sales_funnel', 'mfunnel');
	}

    private function requireApiKey()
    {
        $apiKey = $this->input->get_request_header('X-API-KEY');

        if ($apiKey !== 'gateway-fms') {
            $this->response([
                'status'  => false,
                'message' => 'Invalid API Key',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }
    }

    private function authUser()
    {
        $auth = $this->input->get_request_header('Authorization');

        if (!$auth || stripos($auth, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($auth, 7));

        if ($token === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return $decoded->data ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function requireAuth()
    {
        $u = $this->authUser();

        if (!$u) {
            $this->response([
                'status'  => false,
                'message' => 'Unauthorized',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }

        return $u;
    }

    private function isMgmt($u)
    {
        return ((int)($u->management ?? 0) === 1);
    }

    private function userId($u)
    {
        return (int)($u->user_id ?? $u->id ?? 0);
    }

    private function nullIfEmpty($v)
    {
        if ($v === null) {
            return null;
        }

        $v = trim((string)$v);

        return $v === '' ? null : $v;
    }

    private function normalizePeriod($periodMode, $start, $end)
    {
        $periodMode = strtolower(trim((string)$periodMode));
        $today = date('Y-m-d');

        if ($periodMode === '') {
            $periodMode = 'month';
        }

        switch ($periodMode) {
            case 'daily':
            case 'day':
                if (!$start) {
                    $start = $today;
                }
                if (!$end) {
                    $end = $today;
                }
                break;

            case 'weekly':
            case 'week':
                if (!$start) {
                    $start = date('Y-m-d', strtotime('monday this week'));
                }
                if (!$end) {
                    $end = date('Y-m-d', strtotime('sunday this week'));
                }
                break;

            case 'monthly':
            case 'month':
                if (!$start) {
                    $start = date('Y-m-01');
                }
                if (!$end) {
                    $end = date('Y-m-t');
                }
                $periodMode = 'month';
                break;

            case 'ytd':
                if (!$start) {
                    $start = date('Y-01-01');
                }
                if (!$end) {
                    $end = $today;
                }
                break;

            default:
                if (!$start) {
                    $start = date('Y-01-01');
                }
                if (!$end) {
                    $end = $today;
                }
                break;
        }

        return [
            'period_mode'  => $periodMode,
            'period_start' => $start,
            'period_end'   => $end,
        ];
    }

    /**
     * GET /api/crm/sales-funnel
     */
    public function index_get()
    {
        $this->requireApiKey();

        $u = $this->requireAuth();

        $periodMode = $this->nullIfEmpty(
            $this->input->get('period_mode', true)
            ?: $this->input->get('granularity', true)
        );

        $periodStart = $this->nullIfEmpty(
            $this->input->get('period_start', true)
            ?: $this->input->get('start', true)
        );

        $periodEnd = $this->nullIfEmpty(
            $this->input->get('period_end', true)
            ?: $this->input->get('end', true)
        );

        $period = $this->normalizePeriod($periodMode, $periodStart, $periodEnd);

        $mode = strtolower(trim((string)(
            $this->input->get('mode', true) ?: 'my'
        )));

        $regionId = $this->nullIfEmpty(
            $this->input->get('region_id', true)
            ?: $this->input->get('region', true)
        );

        $salesId = $this->nullIfEmpty(
            $this->input->get('sales_id', true)
            ?: $this->input->get('sales', true)
        );

        $serviceType = $this->nullIfEmpty(
            $this->input->get('service_type', true)
            ?: $this->input->get('shipment_type', true)
        );

        $industry = $this->nullIfEmpty($this->input->get('industry', true));
        $leadSource = $this->nullIfEmpty($this->input->get('lead_source', true));

        /**
         * Access rule:
         * - Non management: selalu own region + own sales.
         * - Management:
         *   mode=my   => own sales.
         *   mode=team => region wajib/fallback token region.
         *   mode=all  => semua yang boleh dia lihat.
         */
        if (!$this->isMgmt($u)) {
			// Non-management: selalu terkunci ke my funnel.
			$mode = 'my';
			$regionId = (string)($u->region_id ?? '');
			$salesId = (string)$this->userId($u);
		} else {
			if ($mode === 'my') {
				// Management lihat data sendiri.
				$salesId = (string)$this->userId($u);

				if (!$regionId) {
					$regionId = (string)($u->region_id ?? '');
				}

			} elseif ($mode === 'sales') {
				// Management pilih sales tertentu dari dropdown.
				if (!$salesId) {
					$salesId = (string)$this->userId($u);
				}

				if (!$regionId) {
					$regionId = (string)($u->region_id ?? '');
				}

			} elseif ($mode === 'team' || $mode === 'region') {
				// Summary region/team.
				if (!$regionId) {
					$regionId = (string)($u->region_id ?? '');
				}

				$salesId = null;

			} elseif ($mode === 'all') {
				// Semua region jika diizinkan.
				// Kalau management non-Jakarta mau dikunci region sendiri, aktifkan block ini.
				/*
				if (strtoupper((string)($u->region_id ?? '')) !== 'JKT' && strtolower((string)($u->region_id ?? '')) !== 'jakarta') {
					$regionId = (string)($u->region_id ?? '');
				}
				*/

				$salesId = null;

			} else {
				$mode = 'my';
				$salesId = (string)$this->userId($u);

				if (!$regionId) {
					$regionId = (string)($u->region_id ?? '');
				}
			}
		}

        $params = [
            'period_start' => $period['period_start'],
            'period_end'   => $period['period_end'],
            'period_mode'  => $period['period_mode'],
            'granularity'  => $period['period_mode'],

            'region_id'    => $regionId,
            'sales_id'     => $salesId,
            'mode'         => $mode,

            'service_type' => $serviceType,
            'industry'     => $industry,
            'lead_source'  => $leadSource,
        ];

        $res = $this->mfunnel->dashboard($params);

        return $this->response($res, RestController::HTTP_OK);
    }
	public function index_options()
	{
		$this->output
			->set_status_header(200)
			->set_output('');
	}
}