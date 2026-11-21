<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Cac_analysis extends RestController
{
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_cac_analysis', 'mcac');
    }

    public function index_options()
    {
        $this->output
            ->set_status_header(200)
            ->set_output('');
    }

    public function index_get()
    {
        try {
            $u = $this->crm_auth->require_user();

            $periodStart = $this->nullIfEmpty(
                $this->input->get('period_start', true)
                ?: $this->input->get('start', true)
            );

            $periodEnd = $this->nullIfEmpty(
                $this->input->get('period_end', true)
                ?: $this->input->get('end', true)
            );

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

            $leadSource = $this->nullIfEmpty($this->input->get('lead_source', true));
            $costCategory = $this->nullIfEmpty($this->input->get('cost_category', true));
            $costScope = $this->nullIfEmpty($this->input->get('cost_scope', true));

            $isMgmt = ((int)($u['management'] ?? 0) === 1);
            $userId = (int)($u['user_id'] ?? 0);
            $userRegion = (string)($u['region_id'] ?? '');

            if (!$isMgmt) {
                $mode = 'my';
                $regionId = $userRegion;
                $salesId = (string)$userId;
            } else {
                if ($mode === 'my') {
                    $salesId = (string)$userId;

                    if (!$regionId) {
                        $regionId = $userRegion;
                    }

                } elseif ($mode === 'sales') {
                    if (!$salesId) {
                        $salesId = (string)$userId;
                    }

                    if (!$regionId) {
                        $regionId = $userRegion;
                    }

                } elseif ($mode === 'region' || $mode === 'team') {
                    if (!$regionId) {
                        $regionId = $userRegion;
                    }

                    $salesId = null;

                } elseif ($mode === 'all') {
                    $salesId = null;

                    /**
                     * Kalau management non-Jakarta perlu dikunci ke region sendiri,
                     * aktifkan block ini.
                     */
                    /*
                    if (strtoupper($userRegion) !== 'JKT' && strtolower($userRegion) !== 'jakarta') {
                        $regionId = $userRegion;
                    }
                    */

                } else {
                    $mode = 'my';
                    $regionId = $regionId ?: $userRegion;
                    $salesId = (string)$userId;
                }
            }

            $params = [
                'period_start'  => $periodStart,
                'period_end'    => $periodEnd,
                'mode'          => $mode,
                'region_id'     => $regionId,
                'sales_id'      => $salesId,
                'service_type'  => $serviceType,
                'lead_source'   => $leadSource,
                'cost_category' => $costCategory,
                'cost_scope'    => $costScope,
            ];

            $res = $this->mcac->dashboard($params);

            return $this->response($res, RestController::HTTP_OK);

        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }

    private function nullIfEmpty($v)
    {
        if ($v === null) {
            return null;
        }

        $v = trim((string)$v);

        return $v === '' ? null : $v;
    }
}