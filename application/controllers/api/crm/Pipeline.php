<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * CRM Pipeline API (CI3 + chriskacerguis/restserver)
 *
 * Access rules (per request):
 * - Non-management: only own data (sales_id = self), region forced to user's region.
 * - Management Jakarta: can access ALL regions or any region.
 * - Management non-Jakarta: can access only own region.
 *
 * Endpoints:
 * - GET /api/crm/pipeline/today
 *     Query: date (Y-m-d), region (string|ALL), sales (numeric|ALL)
 * - GET /api/crm/pipeline/history
 *     Query: from (Y-m-d), to (Y-m-d), region (string|ALL), sales (numeric|ALL)
 *     Returns: per-day bucket summary series for leads/customers/opportunities.
 * - GET /api/crm/pipeline/detail
 *     Query: entity_type (lead|customer|opportunity), bucket (overdue|due_today|next7d),
 *            as_of (Y-m-d), region (string|ALL), sales (numeric|ALL), page, limit
 *     Returns: queue cards (same shape as /today queues items) filtered by bucket.
 * - GET /api/crm/pipeline/timeline
 *     Query: entity_type (lead|customer|opportunity), entity_id (int), from, to, page, limit
 */
class Pipeline extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_pipeline', 'm_pipeline');
    }

    /**
     * Normalize + enforce region/sales scope based on user role.
     *
     * @param array $u user payload from crm_auth
     * @param mixed $regionParam
     * @param mixed $salesParam
     * @return array [region, sales]
     */
    private function resolve_scope(array $u, $regionParam, $salesParam)
    {
        $isManagement = ((int)($u['management'] ?? 0) === 1);
        $userRegion   = (string)($u['region_id'] ?? '');
        $userSalesId  = (string)($u['user_id'] ?? '');

        $region = is_null($regionParam) ? '' : trim((string)$regionParam);
        $sales  = is_null($salesParam)  ? '' : trim((string)$salesParam);

        // Non-management: force my scope
        if (!$isManagement) {
            return [
                'region' => $userRegion,
                'sales'  => $userSalesId,
            ];
        }

        // Management: sales may be ALL or numeric
        if ($sales === '') {
            $sales = 'ALL';
        } else {
            $salesUpper = strtoupper($sales);
            if ($salesUpper === 'ALL') {
                $sales = 'ALL';
            } elseif (!ctype_digit($sales)) {
                // invalid -> ALL
                $sales = 'ALL';
            }
        }

        // Region rules:
        // - Management Jakarta: region may be ALL or any explicit region; default = user's region.
        // - Management non-Jakarta: region forced to user's region.
        $isJakarta = (strcasecmp($userRegion, 'Jakarta') === 0);
        if (!$isJakarta) {
            return [
                'region' => $userRegion,
                'sales'  => $sales,
            ];
        }

        // Jakarta management: allow ALL/any; if empty default to user's region.
        if ($region === '') $region = $userRegion;
        return [
            'region' => $region,
            'sales'  => $sales,
        ];
    }

    /**
     * GET /api/crm/pipeline/today
     */
    public function today_get()
    {
        try {
            $u = $this->crm_auth->require_user();

            $date   = $this->get('date');
            $region = $this->get('region');
            $sales  = $this->get('sales');

            $dateYmd = $this->m_pipeline->sanitize_ymd($date, date('Y-m-d'));

            $scope = $this->resolve_scope($u, $region, $sales);
            $out   = $this->m_pipeline->get_today($scope['region'], $scope['sales'], $dateYmd);

            return $this->response([
                'status' => true,
                'data'   => $out,
            ], RestController::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * GET /api/crm/pipeline/history
     */
    public function history_get()
    {
        try {
            $u = $this->crm_auth->require_user();

            $from   = $this->get('from');
            $to     = $this->get('to');
            $region = $this->get('region');
            $sales  = $this->get('sales');

            $fromYmd = $this->m_pipeline->sanitize_ymd($from, date('Y-m-d', strtotime('-14 days')));
            $toYmd   = $this->m_pipeline->sanitize_ymd($to, date('Y-m-d'));

            // Guard: if from > to, swap
            if ($fromYmd > $toYmd) {
                $tmp = $fromYmd; $fromYmd = $toYmd; $toYmd = $tmp;
            }

            $scope  = $this->resolve_scope($u, $region, $sales);
            $result = $this->m_pipeline->get_history_series($scope['region'], $scope['sales'], $fromYmd, $toYmd);

            return $this->response([
                'status' => true,
                'data'   => $result,
            ], RestController::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * GET /api/crm/pipeline/detail
     */
    public function detail_get()
    {
        try {
            $u = $this->crm_auth->require_user();

            $entityType = strtolower(trim((string)$this->get('entity_type')));
            $bucket     = strtolower(trim((string)$this->get('bucket')));
            $asOf       = $this->m_pipeline->sanitize_ymd($this->get('as_of'), date('Y-m-d'));

            $region = $this->get('region');
            $sales  = $this->get('sales');
            $page   = (int)($this->get('page') ?: 1);
            $limit  = (int)($this->get('limit') ?: 50);
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 1;
            if ($limit > 200) $limit = 200;

            if (!in_array($entityType, ['lead','customer','opportunity'], true)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Invalid entity_type. Allowed: lead, customer, opportunity',
                ], RestController::HTTP_BAD_REQUEST);
            }
            if (!in_array($bucket, ['overdue','due_today','next7d'], true)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Invalid bucket. Allowed: overdue, due_today, next7d',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $scope  = $this->resolve_scope($u, $region, $sales);
            $result = $this->m_pipeline->get_bucket_details([
                'entity_type' => $entityType,
                'bucket'      => $bucket,
                'as_of'       => $asOf,
                'region'      => $scope['region'],
                'sales'       => $scope['sales'],
                'page'        => $page,
                'limit'       => $limit,
            ]);

            return $this->response([
                'status' => true,
                'data'   => $result,
            ], RestController::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * GET /api/crm/pipeline/timeline
     */
    public function timeline_get()
    {
        try {
            $u = $this->crm_auth->require_user();

            $entityType = strtolower(trim((string)$this->get('entity_type')));
            $entityId   = (int)($this->get('entity_id') ?: 0);

            if (!in_array($entityType, ['lead','customer','opportunity'], true) || $entityId <= 0) {
                return $this->response([
                    'status' => false,
                    'message' => 'Invalid entity_type/entity_id',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $from = $this->m_pipeline->sanitize_ymd($this->get('from'), date('Y-m-d', strtotime('-30 days')));
            $to   = $this->m_pipeline->sanitize_ymd($this->get('to'), date('Y-m-d'));
            if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }

            // scope (region/sales) still applied to timeline
            $scope = $this->resolve_scope($u, $this->get('region'), $this->get('sales'));

            $page  = (int)($this->get('page') ?: 1);
            $limit = (int)($this->get('limit') ?: 50);
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 1;
            if ($limit > 200) $limit = 200;

            $result = $this->m_pipeline->activity_timeline([
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'from'        => $from,
                'to'          => $to,
                'region'      => $scope['region'],
                'sales'       => $scope['sales'],
                'page'        => $page,
                'limit'       => $limit,
            ]);

            return $this->response([
                'status' => true,
                'data'   => $result,
            ], RestController::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }
}
