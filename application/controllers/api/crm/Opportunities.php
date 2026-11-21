<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Opportunities extends RestController
{
    private $secret_key = "your-secret-key"; // samakan dengan Login controller

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/crm/M_opportunities', 'mop');
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

    private function jsonBody()
    {
        $raw = $this->input->raw_input_stream;
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function getIdParam()
    {
        $id = (int)(
            $this->input->get('id', true)
            ?: $this->input->get('opportunity_id', true)
        );

        if (!$id) {
            $id = (int)(
                $this->get('id')
                ?: $this->get('opportunity_id')
                ?: $this->post('id')
                ?: $this->put('id')
                ?: $this->delete('id')
            );
        }

        if (!$id) {
            $seg = $this->uri->segment($this->uri->total_segments());
            $id = (int)$seg;
        }

        return $id;
    }

    private function nullIfEmpty($v)
    {
        if ($v === null) {
            return null;
        }

        if (is_string($v)) {
            $t = trim($v);
            return $t === '' ? null : $t;
        }

        return $v;
    }

    private function toIntOrNull($v)
    {
        $v = $this->nullIfEmpty($v);

        if ($v === null) {
            return null;
        }

        if (is_numeric($v)) {
            return (int)$v;
        }

        return null;
    }

    private function toFloatOrNull($v)
    {
        $v = $this->nullIfEmpty($v);

        if ($v === null) {
            return null;
        }

        if (is_numeric($v)) {
            return (float)$v;
        }

        return null;
    }

    private function userId($u)
    {
        return (int)($u->user_id ?? $u->id ?? 0);
    }

    private function normalizePayload($u, $data, $isUpdate = false)
    {
        $isMgmt = $this->isMgmt($u);

        /**
         * Access rule:
         * - Non management: region_id dan owner_sales_id selalu dari token.
         * - Management: boleh override, fallback ke token.
         */
        if (!$isMgmt) {
            $data['region_id'] = (string)($u->region_id ?? '');
            $data['owner_sales_id'] = (string)$this->userId($u);
        } else {
            if (!isset($data['region_id']) || trim((string)$data['region_id']) === '') {
                $data['region_id'] = (string)($u->region_id ?? '');
            }

            if (!isset($data['owner_sales_id']) || trim((string)$data['owner_sales_id']) === '') {
                $data['owner_sales_id'] = (string)$this->userId($u);
            }
        }

        $out = [];

        $out['source_type']    = strtoupper((string)($data['source_type'] ?? ''));
        $out['account_name']   = trim((string)($data['account_name'] ?? ''));
        $out['region_id']      = (string)($data['region_id'] ?? '');
        $out['owner_sales_id'] = $this->toIntOrNull($data['owner_sales_id'] ?? null);

        $leadId = $this->toIntOrNull($data['lead_id'] ?? null);
        $shipperId = $this->toIntOrNull($data['id_shipper'] ?? null);

        if ($out['source_type'] === 'LEAD') {
            $out['lead_id'] = $leadId;
            $out['id_shipper'] = null;
        } elseif ($out['source_type'] === 'CUSTOMER') {
            $out['id_shipper'] = $shipperId;
            $out['lead_id'] = null;
        } else {
            $out['lead_id'] = $leadId;
            $out['id_shipper'] = $shipperId;
        }

        $out['shipment_type']  = (string)($data['shipment_type'] ?? '');
        $out['transport_mode'] = (string)($data['transport_mode'] ?? '');
        $out['sea_type']       = $this->nullIfEmpty($data['sea_type'] ?? null);
        $out['incoterm']       = $this->nullIfEmpty($data['incoterm'] ?? null);
        $out['service_level']  = $this->nullIfEmpty($data['service_level'] ?? null);

        $out['origin']                = $this->nullIfEmpty($data['origin'] ?? null);
        $out['destination']           = $this->nullIfEmpty($data['destination'] ?? null);
        $out['cargo_ready_date']      = $this->nullIfEmpty($data['cargo_ready_date'] ?? null);
        $out['expected_arrival_date'] = $this->nullIfEmpty($data['expected_arrival_date'] ?? null);

        $out['commodity_type']       = $this->nullIfEmpty($data['commodity_type'] ?? null);
        $out['weight_kg']            = $this->toFloatOrNull($data['weight_kg'] ?? null);
        $out['volume_cbm']           = $this->toFloatOrNull($data['volume_cbm'] ?? null);
        $out['chargeable_weight_kg'] = $this->toFloatOrNull($data['chargeable_weight_kg'] ?? null);
        $out['container_size']       = $this->nullIfEmpty($data['container_size'] ?? null);
        $out['hs_code']              = $this->nullIfEmpty($data['hs_code'] ?? null);

        $out['currency']          = $this->nullIfEmpty($data['currency'] ?? null);
        $out['estimated_revenue'] = $this->toFloatOrNull($data['estimated_revenue'] ?? null);
        $out['buying_price']      = $this->toFloatOrNull($data['buying_price'] ?? null);
        $out['competitor_id']     = $this->toIntOrNull($data['competitor_id'] ?? null);
        $out['notes']             = $this->nullIfEmpty($data['notes'] ?? null);

        if (array_key_exists('stage', $data)) {
            $out['stage'] = trim((string)$data['stage']);
        }

        if (array_key_exists('lost_reason', $data)) {
            $out['lost_reason'] = $this->nullIfEmpty($data['lost_reason']);
        }

        /**
         * Cleanup conditional.
         */
        if ($out['transport_mode'] !== 'SEA') {
            $out['sea_type'] = null;
            $out['container_size'] = null;
        }

        if (!($out['transport_mode'] === 'SEA' && $out['sea_type'] === 'FCL')) {
            $out['container_size'] = null;
        }

        if ($out['transport_mode'] !== 'AIR') {
            $out['chargeable_weight_kg'] = null;
        }

        return $out;
    }

    private function validatePayload($data, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate) {
            if (!in_array($data['source_type'] ?? '', ['LEAD', 'CUSTOMER'], true)) {
                $errors[] = 'source_type must be LEAD or CUSTOMER';
            }

            if (trim((string)($data['account_name'] ?? '')) === '') {
                $errors[] = 'account_name is required';
            }
        } else {
            if (
                isset($data['source_type'])
                && !in_array($data['source_type'], ['LEAD', 'CUSTOMER'], true)
            ) {
                $errors[] = 'source_type must be LEAD or CUSTOMER';
            }
        }

        if (($data['source_type'] ?? '') === 'LEAD' && empty($data['lead_id']) && !$isUpdate) {
            $errors[] = 'lead_id is required for LEAD';
        }

        if (($data['source_type'] ?? '') === 'CUSTOMER' && empty($data['id_shipper']) && !$isUpdate) {
            $errors[] = 'id_shipper is required for CUSTOMER';
        }

        if (trim((string)($data['region_id'] ?? '')) === '') {
            $errors[] = 'region_id is required';
        }

        if (empty($data['owner_sales_id'])) {
            $errors[] = 'owner_sales_id is required';
        }

        /**
         * Lost reason validation.
         * Ini masih boleh hardcode di controller.
         * Tapi stage valid/tidak tetap dicek lagi di model via crm_opportunity_stages.
         */
        $badStages = [
            'Budget Constraint',
            'No Decision / Stalled',
            'Internal Policy',
            'Rate Rejected',
            'Lost to Competitor',
            'Service Mismatch',
            'Schedule Mismatch',
            'Space Not Available',
            'Documentation Issue',
            'No Response',
            'Cancelled',
        ];

        if (isset($data['stage']) && in_array((string)$data['stage'], $badStages, true)) {
            if (trim((string)($data['lost_reason'] ?? '')) === '') {
                $errors[] = 'lost_reason is required for lost stages';
            }
        }

        return $errors;
    }

    /**
     * GET /api/crm/opportunities
     * GET /api/crm/opportunities?id=123
     */
    public function index_get()
    {
        $this->requireApiKey();

        /**
         * Untuk list, auth masih optional seperti existing Anda.
         * Kalau mau semua protected, ganti authUser() menjadi requireAuth().
         */
        $u = $this->authUser();

        $oid = (int)(
            $this->input->get('opportunity_id', true)
            ?: $this->input->get('id', true)
            ?: $this->get('opportunity_id')
            ?: $this->get('id')
        );

        if ($oid) {
            $row = $this->mop->getById($oid);

            if (!$row) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Opportunity not found',
                ], RestController::HTTP_NOT_FOUND);
            }

            if ($u && !$this->isMgmt($u)) {
                if (
                    (string)$row->region_id !== (string)($u->region_id ?? '')
                    || (int)$row->owner_sales_id !== $this->userId($u)
                ) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Forbidden',
                    ], RestController::HTTP_FORBIDDEN);
                }
            }

            return $this->response([
                'status' => true,
                'data'   => [(array)$row],
            ], RestController::HTTP_OK);
        }

        $page   = (int)$this->get('page');
        $limit  = (int)$this->get('limit');
        $region = (string)$this->get('region');

        $sales = (string)(
            $this->input->get('sales_id', true)
            ?: $this->input->get('sales', true)
            ?: $this->get('sales_id')
            ?: $this->get('sales')
        );

        $q     = (string)$this->get('q');
        $stage = (string)$this->get('stage');

        if ($u) {
            $isMgmt = $this->isMgmt($u);

            if (!$isMgmt) {
                $region = (string)($u->region_id ?? $region);
                $sales  = (string)$this->userId($u);
            } else {
                if ($region === '') {
                    $region = (string)($u->region_id ?? '');
                }
            }
        }

        $res = $this->mop->list([
            'page'     => $page ?: 1,
            'limit'    => $limit ?: 20,
            'region'   => $region,
            'sales_id' => $sales,
            'q'        => $q,
            'stage'    => $stage,
        ]);

        return $this->response($res, RestController::HTTP_OK);
    }

    /**
     * POST /api/crm/opportunities
     */
    public function index_post()
    {
        $this->requireApiKey();

        $u = $this->requireAuth();
        $body = $this->jsonBody();

        if (!is_array($body)) {
            $body = [];
        }

        $norm = $this->normalizePayload($u, $body, false);

        /**
         * Kalau FE tidak kirim stage, model akan default ke New.
         * Tapi lebih jelas kalau controller juga set default.
         */
        if (empty($norm['stage'])) {
            $norm['stage'] = 'New';
        }

        $errs = $this->validatePayload($norm, false);

        if (!empty($errs)) {
            return $this->response([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $errs,
            ], RestController::HTTP_BAD_REQUEST);
        }

        $now = date('Y-m-d H:i:s');

        $norm['created_at'] = $now;
        $norm['updated_at'] = $now;

        /**
         * Penting untuk stage history.
         * Model M_opportunities::create() akan pakai created_by sebagai changed_by.
         */
        $norm['created_by'] = $this->userId($u);
        $norm['updated_by'] = $this->userId($u);

        $res = $this->mop->create($norm);

        return $this->response($res, RestController::HTTP_OK);
    }

    /**
     * PUT /api/crm/opportunities/{id}
     * PUT /api/crm/opportunities?id=123
     */
    public function index_put()
    {
        $this->requireApiKey();

        $u = $this->requireAuth();

        $id = $this->getIdParam();

        if (!$id) {
            return $this->response([
                'status'  => false,
                'message' => 'Missing opportunity id',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $row = $this->mop->getById($id);

        if (!$row) {
            return $this->response([
                'status'  => false,
                'message' => 'Opportunity not found',
            ], RestController::HTTP_NOT_FOUND);
        }

        if (!$this->isMgmt($u)) {
            if (
                (string)$row->region_id !== (string)($u->region_id ?? '')
                || (int)$row->owner_sales_id !== $this->userId($u)
            ) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Forbidden',
                ], RestController::HTTP_FORBIDDEN);
            }
        }

        $body = $this->jsonBody();

        if (!is_array($body)) {
            $body = [];
        }

        /**
         * Simpan key asli yang benar-benar dikirim FE.
         * Ini supaya field hasil fallback tidak otomatis dianggap field update.
         */
        $originalBodyKeys = array_keys($body);

        /**
         * Untuk normalize update, field basic perlu fallback dari row lama.
         */
        if (!isset($body['source_type'])) {
            $body['source_type'] = $row->source_type;
        }

        if (!isset($body['account_name'])) {
            $body['account_name'] = $row->account_name;
        }

        if (!isset($body['shipment_type'])) {
            $body['shipment_type'] = $row->shipment_type ?? '';
        }

        if (!isset($body['transport_mode'])) {
            $body['transport_mode'] = $row->transport_mode ?? '';
        }

        $norm = $this->normalizePayload($u, $body, true);

        /**
         * Update hanya field yang benar-benar dikirim FE.
         * region_id dan owner_sales_id tetap di-include karena access rule.
         */
        $update = [];

        foreach ($norm as $k => $v) {
            if (
                in_array($k, $originalBodyKeys, true)
                || in_array($k, ['region_id', 'owner_sales_id'], true)
            ) {
                $update[$k] = $v;
            }
        }

        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['updated_by'] = $this->userId($u);

        /**
         * Validate pakai row lama + update baru.
         */
        $mergedForValidation = array_merge((array)$row, $update);

        $errs = $this->validatePayload($mergedForValidation, true);

        if (!empty($errs)) {
            return $this->response([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $errs,
            ], RestController::HTTP_BAD_REQUEST);
        }

        /**
         * Di model M_opportunities::update():
         * - kalau stage berubah, history otomatis ditutup dan insert baru.
         * - kalau stage tidak berubah, hanya update biasa.
         */
        $res = $this->mop->update($id, $update);

        return $this->response($res, RestController::HTTP_OK);
    }

    /**
     * POST /api/crm/opportunities/{id}/change-stage
     * POST /api/crm/opportunities/change-stage?id=123
     *
     * Dipakai untuk Kanban drag/drop atau dropdown stage.
     */
    public function change_stage_post($id = null)
    {
        $this->requireApiKey();

        $u = $this->requireAuth();

        $id = (int)($id ?: $this->getIdParam());

        if (!$id) {
            return $this->response([
                'status'  => false,
                'message' => 'Missing opportunity id',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $row = $this->mop->getById($id);

        if (!$row) {
            return $this->response([
                'status'  => false,
                'message' => 'Opportunity not found',
            ], RestController::HTTP_NOT_FOUND);
        }

        if (!$this->isMgmt($u)) {
            if (
                (string)$row->region_id !== (string)($u->region_id ?? '')
                || (int)$row->owner_sales_id !== $this->userId($u)
            ) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Forbidden',
                ], RestController::HTTP_FORBIDDEN);
            }
        }

        $body = $this->jsonBody();

        if (!is_array($body)) {
            $body = [];
        }

        $newStage = trim((string)($body['stage'] ?? $this->post('stage') ?? ''));

        if ($newStage === '') {
            return $this->response([
                'status'  => false,
                'message' => 'stage is required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $lostReason = null;

        if (array_key_exists('lost_reason', $body)) {
            $lostReason = $this->nullIfEmpty($body['lost_reason']);
        }

        /**
         * Validasi lost_reason.
         */
        $validateData = array_merge((array)$row, [
            'stage'       => $newStage,
            'lost_reason' => $lostReason,
        ]);

        $errs = $this->validatePayload($validateData, true);

        if (!empty($errs)) {
            return $this->response([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $errs,
            ], RestController::HTTP_BAD_REQUEST);
        }

        /**
         * Kalau lost_reason dikirim, update sekaligus.
         * Kalau tidak, cukup changeStage.
         */
        if (array_key_exists('lost_reason', $body)) {
            $res = $this->mop->update($id, [
                'stage'       => $newStage,
                'lost_reason' => $lostReason,
                'updated_at'  => date('Y-m-d H:i:s'),
                'updated_by'  => $this->userId($u),
            ]);
        } else {
            $res = $this->mop->changeStage(
                $id,
                $newStage,
                $this->userId($u)
            );
        }

        return $this->response($res, RestController::HTTP_OK);
    }

    /**
     * DELETE /api/crm/opportunities/{id}
     * DELETE /api/crm/opportunities?id=123
     */
    public function index_delete()
    {
        $this->requireApiKey();

        $u = $this->requireAuth();

        $id = $this->getIdParam();

        if (!$id) {
            return $this->response([
                'status'  => false,
                'message' => 'Missing opportunity id',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $row = $this->mop->getById($id);

        if (!$row) {
            return $this->response([
                'status'  => false,
                'message' => 'Opportunity not found',
            ], RestController::HTTP_NOT_FOUND);
        }

        if (!$this->isMgmt($u)) {
            if (
                (string)$row->region_id !== (string)($u->region_id ?? '')
                || (int)$row->owner_sales_id !== $this->userId($u)
            ) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Forbidden',
                ], RestController::HTTP_FORBIDDEN);
            }
        }

        $res = $this->mop->delete($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->userId($u),
        ]);

        return $this->response($res, RestController::HTTP_OK);
    }
}