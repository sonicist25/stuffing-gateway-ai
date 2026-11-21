<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Ai_quote_drafts extends RestController
{
    private $secret_key;

    public function __construct()
    {
        parent::__construct();

        $this->secret_key = (string)(
            getenv('JWT_SECRET')
            ?: $this->config->item('jwt_secret')
            ?: $this->config->item('jwt_key')
            ?: 'your-secret-key'
        );

        $this->load->model('api/crm/M_ai_quote_drafts', 'drafts');
        $this->load->model('api/crm/M_quotations', 'quotations');
        $this->master_db = $this->load->database('master', true);
    }

    private function requireApiKey()
    {
        $apiKey = $this->input->get_request_header('X-API-KEY');
        if ($apiKey !== 'gateway-fms') {
            $this->response([
                'status' => false,
                'message' => 'Invalid API Key',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }
    }

    private function authUser()
    {
        $auth = $this->input->get_request_header('Authorization');
        if (!$auth || stripos($auth, 'Bearer ') !== 0) return null;

        $token = trim(substr($auth, 7));
        if ($token === '') return null;

        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return $decoded->data ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function requireAuth()
    {
        $user = $this->authUser();
        if (!$user) {
            $this->response([
                'status' => false,
                'message' => 'Unauthorized',
            ], RestController::HTTP_UNAUTHORIZED);
            exit;
        }
        return $user;
    }

    private function userId($user)
    {
        return (int)($user->user_id ?? $user->id ?? 0);
    }

    private function isManagement($user)
    {
        return (int)($user->management ?? 0) === 1;
    }

    private function jsonBody()
    {
        $body = json_decode($this->input->raw_input_stream, true);
        return is_array($body) ? $body : [];
    }

    private function decodeJson($value, $fallback = [])
    {
        if (is_array($value)) return $value;
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : $fallback;
    }

    private function firstHeaderValue(array $header, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $header)) continue;

            $value = $header[$key];
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') continue;
            }

            if ($value !== null) return $value;
        }

        return $default;
    }

    private function isTruthy($value)
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (int)$value === 1;

        return in_array(strtoupper(trim((string)$value)), [
            '1', 'TRUE', 'YES', 'Y', 'DG', 'DANGEROUS', 'DANGEROUS GOODS', 'HAZARDOUS'
        ], true);
    }

    private function cargoTypeFromHeader(array $header)
    {
        $rawType = strtoupper(trim((string)$this->firstHeaderValue($header, [
            'cargo_type', 'commodity_type', 'goods_type'
        ], '')));

        $dgClass = trim((string)$this->firstHeaderValue($header, [
            'dg_class', 'dangerous_goods_class', 'imo_class'
        ], ''));

        $isDg = $this->isTruthy($this->firstHeaderValue($header, [
            'is_dg', 'dangerous_goods', 'dg'
        ], false));

        if (
            $isDg ||
            $dgClass !== '' ||
            preg_match('/(^|[^A-Z])(DG|DANGEROUS|HAZARDOUS)([^A-Z]|$)/', $rawType)
        ) {
            return 'DG';
        }

        return 'GENERAL';
    }

    private function rateInformation(array $draft, array $header)
    {
        $rateInfo = $draft['rate_meta']
            ?? $draft['rate_information']
            ?? $header['rate_information']
            ?? [];

        if (is_string($rateInfo)) {
            $decoded = json_decode($rateInfo, true);
            if (is_array($decoded)) return $decoded;
            return $rateInfo !== '' ? [['remarks' => $rateInfo]] : [];
        }

        return is_array($rateInfo) ? $rateInfo : [];
    }

    private function presentDraft(array $row)
    {
        return [
            'draft_id'       => (int)$row['draft_id'],
            'session_id'     => (string)$row['session_id'],
            'opportunity_id' => !empty($row['opportunity_id']) ? (int)$row['opportunity_id'] : null,
            'quote_id'       => !empty($row['quote_id']) ? (int)$row['quote_id'] : null,
            'account_name'   => $row['account_name'] ?? null,
            'status'         => (string)$row['status'],
            'version_no'     => (int)$row['version_no'],
            'draft'          => $this->decodeJson($row['draft_json'] ?? null, []),
            'messages'       => $this->decodeJson($row['messages_json'] ?? null, []),
            'pending_context'=> $this->decodeJson($row['pending_context_json'] ?? null, null),
            'created_at'     => $row['created_at'] ?? null,
            'updated_at'     => $row['updated_at'] ?? null,
            'converted_at'   => $row['converted_at'] ?? null,
        ];
    }

    /** POST /api/crm/ai_quote_drafts */
    public function index_post()
    {
        $this->requireApiKey();
        $user = $this->requireAuth();
        $body = $this->jsonBody();

        try {
            $draftId = $this->drafts->saveDraft(
                $body,
                $this->userId($user),
                (string)($user->region_id ?? ''),
                $this->isManagement($user)
            );

            $row = $this->drafts->getAccessible(
                $draftId,
                $this->userId($user),
                (string)($user->region_id ?? ''),
                $this->isManagement($user)
            );

            return $this->response([
                'status' => true,
                'message' => 'AI quote draft saved',
                'data' => $this->presentDraft($row),
            ], RestController::HTTP_OK);
        } catch (InvalidArgumentException $e) {
            return $this->response([
                'status' => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            log_message('error', 'Ai_quote_drafts/index_post: ' . $e->getMessage());
            return $this->response([
                'status' => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /api/crm/ai_quote_drafts?id=123 */
    public function index_get()
    {
        $this->requireApiKey();
        $user = $this->requireAuth();
        $draftId = (int)($this->get('id') ?: $this->get('draft_id'));

        if ($draftId <= 0) {
            return $this->response([
                'status' => false,
                'message' => 'draft_id is required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $row = $this->drafts->getAccessible(
            $draftId,
            $this->userId($user),
            (string)($user->region_id ?? ''),
            $this->isManagement($user)
        );

        if (!$row) {
            return $this->response([
                'status' => false,
                'message' => 'Draft not found or forbidden',
            ], RestController::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status' => true,
            'data' => $this->presentDraft($row),
        ], RestController::HTTP_OK);
    }

    private function itemRows($type, $rows)
    {
        $out = [];
        if (!is_array($rows)) return $out;

        foreach ($rows as $index => $item) {
            if (!is_array($item)) continue;

            $basis = strtoupper(trim((string)($item['pricing_basis'] ?? 'FIXED')));
            $excludeFromTotal = in_array($basis, [
                'AT_COST', 'IF_ANY', 'PER_RECEIPT', 'QUOTE_REQUIRED',
                'COLLECT', 'EXCLUDED', 'INFO'
            ], true) && (float)($item['amount'] ?? 0) <= 0;

            $out[] = [
                'item_type'       => $type,
                'line_no'         => $index + 1,
                'charge_code'     => $item['charge_code'] ?? null,
                'description'     => trim((string)($item['description'] ?? $item['name'] ?? '')),
                'charge_detail'   => $item['charge_detail'] ?? null,
                'dest_code'       => $item['dest_code'] ?? null,
                'dest_name'       => $item['dest_name'] ?? null,
                'pricing_basis'   => $basis,
                'qty'             => (float)($item['qty'] ?? 0),
                'uom'             => $item['uom'] ?? null,
                'unit_price'      => (float)($item['unit_price'] ?? 0),
                'min_charge'      => (float)($item['min_charge'] ?? 0),
                'amount'          => (float)($item['amount'] ?? 0),
                'include_in_total'=> array_key_exists('include_in_total', $item)
                    ? (int)$item['include_in_total']
                    : ($excludeFromTotal ? 0 : 1),
                'sub_items'       => is_array($item['sub_items'] ?? null) ? $item['sub_items'] : [],
                'sort_order'      => $index + 1,
            ];
        }

        return $out;
    }

    private function quotationPayload(array $draftRow, $opportunityId)
    {
        $draft = $this->decodeJson($draftRow['draft_json'] ?? null, []);
        $header = is_array($draft['header'] ?? null) ? $draft['header'] : [];

        $items = array_merge(
            $this->itemRows('FREIGHT', $draft['freight'] ?? []),
            $this->itemRows('ORIGIN', $draft['origin'] ?? []),
            $this->itemRows('DESTINATION', $draft['destination'] ?? [])
        );

        $currency = strtoupper(trim((string)($header['currency'] ?? '')));
        if ($currency === '') {
            foreach (['freight', 'origin', 'destination'] as $group) {
                if (!empty($draft[$group][0]['curr'])) {
                    $currency = strtoupper((string)$draft[$group][0]['curr']);
                    break;
                }
            }
        }
        if ($currency === '') $currency = 'IDR';

        $cargoType = $this->cargoTypeFromHeader($header);
        $dgClass = trim((string)$this->firstHeaderValue($header, [
            'dg_class', 'dangerous_goods_class', 'imo_class'
        ], ''));
        $hsCode = trim((string)$this->firstHeaderValue($header, [
            'hs_code', 'hscode', 'hs'
        ], ''));

        if ($cargoType !== 'DG') {
            $dgClass = '';
        }

        return [
            'source_ai_draft_id' => (int)$draftRow['draft_id'],
            'opportunity_id'     => (int)$opportunityId,
            'account_name'       => '', // wajib diisi dari Opportunity saat convert; jangan dari AI draft
            'status'             => 'DRAFT',
            'quote_date'         => date('Y-m-d'),
            'valid_until'        => date('Y-m-d', strtotime('+14 days')),
            'currency'           => $currency,
            'cargo_type'         => $cargoType,
            'dg_class'           => $dgClass !== '' ? $dgClass : null,
            'hs_code'            => $hsCode !== '' ? $hsCode : null,
            'rate_information'   => $this->rateInformation($draft, $header),
            'show_total'         => 1,
            'multi_destination'  => !empty($draft['destination']) && count($draft['destination']) > 1 ? 1 : 0,
            'items'              => $items,
        ];
    }

    /** POST /api/crm/ai_quote_drafts/convert */
    public function convert_post()
    {
        $this->requireApiKey();
        $user = $this->requireAuth();
        $body = $this->jsonBody();

        $draftId = (int)($body['draft_id'] ?? 0);
        $opportunityId = (int)($body['opportunity_id'] ?? 0);

        if ($draftId <= 0 || $opportunityId <= 0) {
            return $this->response([
                'status' => false,
                'message' => 'draft_id and opportunity_id are required',
            ], RestController::HTTP_BAD_REQUEST);
        }

        $userId = $this->userId($user);
        $regionId = (string)($user->region_id ?? '');
        $isManagement = $this->isManagement($user);

        $draft = $this->drafts->getAccessible($draftId, $userId, $regionId, $isManagement);
        if (!$draft) {
            return $this->response([
                'status' => false,
                'message' => 'Draft not found or forbidden',
            ], RestController::HTTP_NOT_FOUND);
        }

        // Idempotent: draft yang sudah dikonversi tidak membuat quotation kedua.
        if (!empty($draft['quote_id'])) {
            return $this->response([
                'status' => true,
                'message' => 'Draft already converted',
                'data' => [
                    'draft_id' => $draftId,
                    'opportunity_id' => (int)$draft['opportunity_id'],
                    'quote_id' => (int)$draft['quote_id'],
                ],
            ], RestController::HTTP_OK);
        }

        $opportunity = $this->master_db
            ->where('opportunity_id', $opportunityId)
            ->where('deleted_at IS NULL', null, false)
            ->get('crm_opportunities')
            ->row_array();

        if (!$opportunity) {
            return $this->response([
                'status' => false,
                'message' => 'Opportunity not found',
            ], RestController::HTTP_NOT_FOUND);
        }

        if (!$isManagement && (
            (string)$opportunity['region_id'] !== $regionId ||
            (int)$opportunity['owner_sales_id'] !== $userId
        )) {
            return $this->response([
                'status' => false,
                'message' => 'Opportunity tidak dapat diakses oleh user ini.',
            ], RestController::HTTP_FORBIDDEN);
        }

        try {
            // Simpan hubungan opportunity lebih dulu. Bila pembuatan quotation gagal,
            // draft tetap dapat di-retry tanpa membuat opportunity kedua.
            $this->drafts->attachOpportunity($draftId, $opportunityId);

            $existingQuote = $this->quotations->find_by_ai_draft_id($draftId);
            if ($existingQuote) {
                $quoteId = (int)$existingQuote['quote_id'];
            } else {
                $payload = $this->quotationPayload($draft, $opportunityId);

                // Source of truth account = Opportunity. Jangan pernah ambil account_name dari AI draft.
                $payload['account_name'] = trim((string)($opportunity['account_name'] ?? ''));
                if ($payload['account_name'] === '') {
                    throw new RuntimeException('Opportunity account_name kosong. Lengkapi Opportunity sebelum membuat quotation.');
                }

                $quoteId = (int)$this->quotations->create($payload, $userId);
            }

            if ($quoteId <= 0) throw new RuntimeException('Quotation gagal dibuat.');

            $this->drafts->markConverted($draftId, $opportunityId, $quoteId);

            return $this->response([
                'status' => true,
                'message' => 'AI draft converted to quotation',
                'data' => [
                    'draft_id' => $draftId,
                    'opportunity_id' => $opportunityId,
                    'quote_id' => $quoteId,
                ],
            ], RestController::HTTP_CREATED);
        } catch (Throwable $e) {
            log_message('error', 'Ai_quote_drafts/convert_post: ' . $e->getMessage());
            return $this->response([
                'status' => false,
                'message' => $e->getMessage(),
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
