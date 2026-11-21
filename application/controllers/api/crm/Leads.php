<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Leads extends RestController
{
    const HTTP_CONFLICT = 409;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_leads');
    }

    /* =========================================================
     * AUTH / SCOPE
     * ========================================================= */

    private function require_user()
    {
        try {
            $u = $this->crm_auth->require_user();
        } catch (Throwable $e) {
            $this->response([
                'status'  => false,
                'message' => 'Unauthorized',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }

        if (!is_array($u) || empty($u['user_id']) || empty($u['region_id'])) {
            $this->response([
                'status'  => false,
                'message' => 'Invalid CRM user context',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }

        return $u;
    }

    private function is_management(array $u)
    {
        return (int)($u['management'] ?? 0) === 1;
    }

    /**
     * Rule:
     * - Semua user SELALU dikunci ke region dari token/login.
     * - Management/Branch Manager: semua sales dalam region sendiri.
     * - Sales biasa: hanya lead miliknya sendiri.
     */
    private function access_scope(array $u, $requestedSalesId = null)
    {
        $regionId = trim((string)$u['region_id']);
        $userId   = (int)$u['user_id'];

        if ($regionId === '' || $userId <= 0) {
            throw new RuntimeException('Invalid user access scope');
        }

        if (!$this->is_management($u)) {
            return [
                'region_id' => $regionId,
                'sales_id'  => $userId,
            ];
        }

        // Management boleh filter sales tertentu, tetapi sales harus tetap
        // berasal dari region manager tersebut.
        $requestedSalesId = trim((string)$requestedSalesId);
        if ($requestedSalesId !== '' && strtoupper($requestedSalesId) !== 'ALL') {
            $salesId = (int)$requestedSalesId;
            if ($salesId <= 0 || !$this->M_leads->sales_belongs_to_region($salesId, $regionId)) {
                throw new RuntimeException('Sales tidak berada pada region ' . $regionId);
            }

            return [
                'region_id' => $regionId,
                'sales_id'  => $salesId,
            ];
        }

        return [
            'region_id' => $regionId,
            'sales_id'  => null, // semua sales, tetapi tetap dalam region sendiri
        ];
    }

    private function get_id_param($fallback = null)
    {
        $id = (int)$fallback;
        if ($id > 0) return $id;

        $id = (int)($this->get('id') ?: $this->get('id_lead'));
        if ($id > 0) return $id;

        $seg = (int)$this->uri->segment($this->uri->total_segments());
        return $seg > 0 ? $seg : 0;
    }

    /* =========================================================
     * CREATE
     * ========================================================= */

    public function index_post()
    {
        $u = $this->require_user();

        try {
            // Region dari request sengaja DIABAIKAN.
            $scope = $this->access_scope($u, $this->post('sales_id'));

            $companyName = trim((string)$this->post('company_name'));
            $force = (int)$this->get('force') === 1;

            if ($companyName === '') {
                return $this->response([
                    'status'  => false,
                    'message' => 'company_name is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $duplicates = $this->M_leads->check_duplicate($companyName, $scope['region_id']);
            if ($duplicates && !$force) {
                return $this->response([
                    'status'     => false,
                    'message'    => 'Company already exists or similar found',
                    'duplicates' => $duplicates,
                ], RestController::HTTP_CONFLICT);
            }

            $data = $this->post();
            if (!is_array($data)) $data = [];

            unset($data['id_lead']);
            $data['company_name'] = $companyName;
            $data['region_id'] = $scope['region_id'];

            // Management yang tidak memilih sales default ke dirinya sendiri untuk
            // owner record baru. Sales biasa memang selalu dirinya sendiri.
            $data['sales_id'] = $scope['sales_id'] !== null
                ? (int)$scope['sales_id']
                : (int)$u['user_id'];

            $id = $this->M_leads->insert_lead($data);
            if ($id <= 0) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Failed to create lead',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $response = [
                'status'    => true,
                'message'   => 'Lead created successfully',
                'id_lead'   => $id,
                'region_id' => $scope['region_id'],
            ];

            if ($force && $duplicates) {
                $response['duplicates'] = $duplicates;
                $response['note'] = 'Inserted despite similar company names';
            }

            return $this->response($response, RestController::HTTP_CREATED);

        } catch (Throwable $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_FORBIDDEN);
        }
    }

    /* =========================================================
     * LIST
     * ========================================================= */

    public function index_get()
    {
        $u = $this->require_user();

        try {
            // region_id GET tidak pernah dipakai sebagai access scope.
            $scope = $this->access_scope($u, $this->get('sales_id'));

            $q = trim((string)$this->get('q'));
            $page = max(1, (int)$this->get('page'));
            $limit = max(1, min(100, (int)$this->get('limit') ?: 20));
            $offset = ($page - 1) * $limit;

            $result = $this->M_leads->get_leads(
                $scope['sales_id'],
                $scope['region_id'],
                $limit,
                $offset,
                $q
            );

            // Empty list tetap 200 agar FE tidak menganggapnya API error.
            return $this->response([
                'status'           => true,
                'page'             => $page,
                'limit'            => $limit,
                'total'            => (int)$result['total'],
                'effective_region' => $scope['region_id'],
                'effective_sales'  => $scope['sales_id'] === null ? 'ALL_REGION' : (int)$scope['sales_id'],
                'data'             => $result['data'],
            ], RestController::HTTP_OK);

        } catch (Throwable $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_FORBIDDEN);
        }
    }

    /* =========================================================
     * DETAIL
     * ========================================================= */

    public function lead_get($id_lead = null)
    {
        $u = $this->require_user();
        $id = $this->get_id_param($id_lead);

        if ($id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Lead ID required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        try {
            $scope = $this->access_scope($u, null);
            $lead = $this->M_leads->get_lead_by_id($id, $scope['region_id'], $scope['sales_id']);

            if (!$lead) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Lead not found or not accessible',
                ], RestController::HTTP_NOT_FOUND);
            }

            return $this->response([
                'status' => true,
                'data'   => $lead,
            ], RestController::HTTP_OK);

        } catch (Throwable $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_FORBIDDEN);
        }
    }

    /* =========================================================
     * UPDATE
     * ========================================================= */

    public function index_put($id = null)
    {
        $u = $this->require_user();
        $id = $this->get_id_param($id);

        if ($id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Lead ID required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        try {
            // Access ke record lama harus berdasarkan user login, bukan sales_id baru.
            $baseScope = $this->access_scope($u, null);
            $old = $this->M_leads->get_lead_by_id($id, $baseScope['region_id'], $baseScope['sales_id']);
            if (!$old) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Lead not found or not accessible',
                ], RestController::HTTP_NOT_FOUND);
            }

            $data = $this->put();
            if (!is_array($data)) $data = [];

            unset($data['id_lead'], $data['region_id']);

            // Region tidak pernah boleh dipindahkan via endpoint Lead.
            if (!$this->is_management($u)) {
                // Sales biasa tidak boleh memindahkan ownership.
                $data['sales_id'] = (int)$u['user_id'];
            } elseif (array_key_exists('sales_id', $data)) {
                $newSalesId = (int)$data['sales_id'];
                if (!$this->M_leads->sales_belongs_to_region($newSalesId, $baseScope['region_id'])) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Sales tujuan tidak berada pada region ' . $baseScope['region_id'],
                    ], RestController::HTTP_FORBIDDEN);
                }
            }

            $update = $this->M_leads->update_lead(
                $id,
                $data,
                $baseScope['region_id'],
                $baseScope['sales_id']
            );

            return $this->response([
                'status'  => (bool)$update,
                'message' => $update ? 'Lead updated successfully' : 'Failed to update lead',
            ], $update ? RestController::HTTP_OK : RestController::HTTP_BAD_REQUEST);

        } catch (Throwable $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_FORBIDDEN);
        }
    }

    /* =========================================================
     * DELETE
     * ========================================================= */

    public function index_delete($id = null)
    {
        $u = $this->require_user();
        $id = $this->get_id_param($id);

        if ($id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Lead ID required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        try {
            $scope = $this->access_scope($u, null);

            // Cek dulu supaya response tidak mengklaim sukses untuk ID region lain.
            $old = $this->M_leads->get_lead_by_id($id, $scope['region_id'], $scope['sales_id']);
            if (!$old) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Lead not found or not accessible',
                ], RestController::HTTP_NOT_FOUND);
            }

            $delete = $this->M_leads->delete_lead($id, $scope['region_id'], $scope['sales_id']);

            return $this->response([
                'status'  => (bool)$delete,
                'message' => $delete ? 'Lead deleted successfully' : 'Failed to delete lead',
            ], $delete ? RestController::HTTP_OK : RestController::HTTP_BAD_REQUEST);

        } catch (Throwable $e) {
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_FORBIDDEN);
        }
    }
}
