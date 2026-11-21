<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Quotations extends RestController
{
    protected $methods = [
        'index_get'   => ['key' => false],
        'request_post'=> ['key' => false],
    ];

    /** @var CI_DB_query_builder */
    private $master_db;

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, x-api-key');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        parent::__construct();

        $this->load->library('Customer_auth');
        $this->load->model('api/crm/M_quotations', 'm_quotations');
        $this->master_db = $this->load->database('master', true);
    }

    /**
     * GET /api/quotations
     *
     * Query:
     * - quote_id  optional: detail quotation
     * - shipper_id optional: filter ke satu shipper yang diizinkan
     * - page, limit, q, status
     */
    public function index_get()
    {
        try {
            $auth = $this->customer_auth->authenticate('quotation.view');

            if (empty($auth['ok'])) {
                return $this->response([
                    'status'  => false,
                    'message' => $auth['message'],
                ], (int) $auth['status']);
            }

            $shipperResolution = $this->resolveReadShipperIds($auth);
            if (!$shipperResolution['ok']) {
                return $this->response([
                    'status'  => false,
                    'message' => $shipperResolution['message'],
                ], RestController::HTTP_FORBIDDEN);
            }

            $shipperIds = $shipperResolution['shipper_ids'];
            $quoteId    = (int) ($this->get('quote_id') ?? 0);

            if ($quoteId > 0) {
                $detail = $this->findQuotationDetail($quoteId, $shipperIds);

                if (!$detail) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Quotation not found or unauthorized',
                    ], RestController::HTTP_NOT_FOUND);
                }

                return $this->response([
                    'status' => true,
                    'data'   => $detail,
                    'meta'   => [
                        'shipper_ids' => $shipperIds,
                    ],
                ], RestController::HTTP_OK);
            }

            $filters = [
                'page'   => max(1, (int) ($this->get('page') ?? 1)),
                'limit'  => min(100, max(1, (int) ($this->get('limit') ?? 20))),
                'q'      => trim((string) ($this->get('q') ?? '')),
                'status' => trim((string) ($this->get('status') ?? '')),
            ];

            $out = $this->getCombinedQuotationList($shipperIds, $filters);

            return $this->response($out, RestController::HTTP_OK);
        } catch (Throwable $e) {
            log_message('error', 'Quotations/index_get failed: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Server Error.',
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/quotations/request
     *
     * Untuk user yang mengakses beberapa shipper, body boleh mengirim:
     * {
     *   "id_shipper": 4407,
     *   ...
     * }
     *
     * Jika id_shipper tidak dikirim, shipper default user yang digunakan.
     */
    public function request_post()
    {
        try {
            $auth = $this->customer_auth->authenticate('quotation.request');

            if (empty($auth['ok'])) {
                return $this->response([
                    'status'  => false,
                    'message' => $auth['message'],
                ], (int) $auth['status']);
            }

            $body = json_decode($this->input->raw_input_stream, true);

            if (!is_array($body)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid data format',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $body['origin']      = trim((string) ($body['origin'] ?? ''));
            $body['destination'] = trim((string) ($body['destination'] ?? ''));

            if ($body['origin'] === '' || $body['destination'] === '') {
                return $this->response([
                    'status'  => false,
                    'message' => 'Origin and Destination are required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $requestedShipperId = (int) ($body['id_shipper'] ?? 0);
            $idShipper = $this->resolveWriteShipperId($auth, $requestedShipperId);

            if ($idShipper <= 0) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Shipper tidak termasuk dalam akses user.',
                ], RestController::HTTP_FORBIDDEN);
            }

            // Jangan biarkan model mengambil id_shipper mentah dari payload.
            unset($body['id_shipper']);

            $requestId = $this->m_quotations->create_request($idShipper, $body);

            if (!$requestId) {
                throw new RuntimeException('Quotation request gagal disimpan.');
            }

            // Kegagalan email/notifikasi tidak boleh membatalkan request yang sudah tersimpan.
            try {
                $this->notifySalesTeam((int) $requestId, $body, $idShipper);
            } catch (Throwable $notifyError) {
                log_message(
                    'error',
                    'Quotation notification failed for request ' .
                    $requestId . ': ' . $notifyError->getMessage()
                );
            }

            return $this->response([
                'status'  => true,
                'message' => 'Your request has been submitted. Our sales team will contact you soon.',
                'data'    => [
                    'request_id' => (int) $requestId,
                    'id_shipper' => $idShipper,
                ],
            ], RestController::HTTP_CREATED);
        } catch (Throwable $e) {
            log_message('error', 'Quotations/request_post failed: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Quotation request gagal diproses.',
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Untuk list/detail:
     * - tanpa query shipper_id => seluruh shipper yang diizinkan
     * - dengan query shipper_id => hanya shipper tersebut
     */
    private function resolveReadShipperIds(array $auth)
    {
        $allowed = array_values(array_unique(array_map(
            'intval',
            $auth['shipper_ids'] ?? []
        )));

        $allowed = array_values(array_filter($allowed, static function ($id) {
            return $id > 0;
        }));

        if (!$allowed) {
            return [
                'ok'          => false,
                'message'     => 'No active shipper access.',
                'shipper_ids' => [],
            ];
        }

        $requested = (int) ($this->get('shipper_id') ?? 0);

        if ($requested <= 0) {
            return [
                'ok'          => true,
                'shipper_ids' => $allowed,
            ];
        }

        if (!in_array($requested, $allowed, true)) {
            return [
                'ok'          => false,
                'message'     => 'Shipper tidak termasuk dalam akses user.',
                'shipper_ids' => [],
            ];
        }

        return [
            'ok'          => true,
            'shipper_ids' => [$requested],
        ];
    }

    /**
     * Untuk transaksi tulis harus menghasilkan tepat satu shipper.
     */
    private function resolveWriteShipperId(array $auth, $requestedShipperId)
    {
        $allowed = array_values(array_unique(array_map(
            'intval',
            $auth['shipper_ids'] ?? []
        )));

        if ($requestedShipperId > 0) {
            return in_array($requestedShipperId, $allowed, true)
                ? $requestedShipperId
                : 0;
        }

        $defaultId = (int) ($auth['user']['id_shipper'] ?? 0);
        if ($defaultId > 0 && in_array($defaultId, $allowed, true)) {
            return $defaultId;
        }

        foreach (($auth['shipper_access'] ?? []) as $access) {
            if (!empty($access['is_default'])) {
                $candidate = (int) ($access['id_shipper'] ?? 0);
                if (in_array($candidate, $allowed, true)) {
                    return $candidate;
                }
            }
        }

        return count($allowed) === 1 ? $allowed[0] : 0;
    }

    /**
     * Tetap kompatibel dengan model lama yang menerima satu id_shipper.
     */
    private function findQuotationDetail($quoteId, array $shipperIds)
    {
        if (method_exists($this->m_quotations, 'get_customer_detail_multi')) {
            return $this->m_quotations->get_customer_detail_multi(
                $quoteId,
                $shipperIds
            );
        }

        foreach ($shipperIds as $idShipper) {
            $detail = $this->m_quotations->get_customer_detail(
                $quoteId,
                $idShipper
            );

            if ($detail) {
                if (is_array($detail) && !isset($detail['id_shipper'])) {
                    $detail['id_shipper'] = $idShipper;
                }

                return $detail;
            }
        }

        return null;
    }

    /**
     * Jika model sudah memiliki get_customer_list_multi(), gunakan itu.
     * Jika belum, controller menggabungkan hasil model lama per shipper.
     */
    private function getCombinedQuotationList(array $shipperIds, array $filters)
    {
        if (method_exists($this->m_quotations, 'get_customer_list_multi')) {
            $out = $this->m_quotations->get_customer_list_multi(
                $shipperIds,
                $filters
            );

            if (!is_array($out)) {
                $out = [
                    'status' => true,
                    'data'   => [],
                ];
            }

            $out['meta'] = array_merge(
                is_array($out['meta'] ?? null) ? $out['meta'] : [],
                ['shipper_ids' => $shipperIds]
            );

            return $out;
        }

        // Satu shipper: pertahankan format respons model lama.
        if (count($shipperIds) === 1) {
            $out = $this->m_quotations->get_customer_list(
                $shipperIds[0],
                $filters
            );

            if (!is_array($out)) {
                $out = [
                    'status' => true,
                    'data'   => [],
                ];
            }

            $out['meta'] = array_merge(
                is_array($out['meta'] ?? null) ? $out['meta'] : [],
                ['shipper_ids' => $shipperIds]
            );

            return $out;
        }

        // Ambil data yang cukup dari setiap shipper, kemudian pagination setelah digabung.
        $page  = (int) $filters['page'];
        $limit = (int) $filters['limit'];
        $fetchLimit = min(500, max($limit, $page * $limit));

        $combined = [];

        foreach ($shipperIds as $idShipper) {
            $perShipperFilters = $filters;
            $perShipperFilters['page']  = 1;
            $perShipperFilters['limit'] = $fetchLimit;

            $out = $this->m_quotations->get_customer_list(
                $idShipper,
                $perShipperFilters
            );

            foreach ($this->extractRows($out) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (!isset($row['id_shipper'])) {
                    $row['id_shipper'] = $idShipper;
                }

                $combined[] = $row;
            }
        }

        $combined = $this->deduplicateRows($combined);
        $this->sortQuotationRows($combined);

        $total  = count($combined);
        $offset = ($page - 1) * $limit;
        $rows   = array_slice($combined, $offset, $limit);

        return [
            'status' => true,
            'data'   => $rows,
            'pagination' => [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'total_pages' => $limit > 0
                    ? (int) ceil($total / $limit)
                    : 0,
            ],
            'meta' => [
                'shipper_ids' => $shipperIds,
            ],
        ];
    }

    private function extractRows($out)
    {
        if (!is_array($out)) {
            return [];
        }

        if ($this->isListArray($out)) {
            return $out;
        }

        foreach (['data', 'rows', 'items', 'quotations'] as $key) {
            if (!array_key_exists($key, $out) || !is_array($out[$key])) {
                continue;
            }

            if ($this->isListArray($out[$key])) {
                return $out[$key];
            }

            $nested = $this->extractRows($out[$key]);
            if ($nested) {
                return $nested;
            }
        }

        return [];
    }

    private function isListArray(array $value)
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    private function deduplicateRows(array $rows)
    {
        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $idShipper = (int) ($row['id_shipper'] ?? 0);
            $recordId = $row['quote_id']
                ?? $row['quotation_id']
                ?? $row['id']
                ?? null;

            $key = $recordId !== null
                ? $idShipper . ':' . (string) $recordId
                : md5(json_encode($row));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $row;
        }

        return $result;
    }

    private function sortQuotationRows(array &$rows)
    {
        usort($rows, static function ($a, $b) {
            $dateKeys = [
                'updated_at',
                'created_at',
                'quotation_date',
                'quote_date',
                'date',
            ];

            $dateA = '';
            $dateB = '';

            foreach ($dateKeys as $key) {
                if ($dateA === '' && !empty($a[$key])) {
                    $dateA = (string) $a[$key];
                }
                if ($dateB === '' && !empty($b[$key])) {
                    $dateB = (string) $b[$key];
                }
            }

            $timeA = $dateA !== '' ? strtotime($dateA) : false;
            $timeB = $dateB !== '' ? strtotime($dateB) : false;

            $timeA = $timeA !== false ? $timeA : 0;
            $timeB = $timeB !== false ? $timeB : 0;

            if ($timeA === $timeB) {
                $idA = (int) ($a['quote_id'] ?? $a['quotation_id'] ?? $a['id'] ?? 0);
                $idB = (int) ($b['quote_id'] ?? $b['quotation_id'] ?? $b['id'] ?? 0);
                return $idB <=> $idA;
            }

            return $timeB <=> $timeA;
        });
    }

    /**
     * Notifikasi CRM dan email.
     * Tidak boleh membuat request quotation gagal jika layanan email bermasalah.
     */
    private function notifySalesTeam($requestId, array $payload, $idShipper)
    {
        $result = $this->master_db
            ->select([
                's.shipper_name',
                'r.user_id AS sales_user_id',
                'r.email AS sales_email',
                'r.management AS is_management',
            ])
            ->from('mstshipper s')
            ->join('refsales r', 's.id_sales = r.id', 'left')
            ->where('s.id_shipper', $idShipper)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$result) {
            log_message(
                'error',
                'Quotation notification skipped: shipper not found ' . $idShipper
            );
            return false;
        }

        $shipperName  = (string) ($result['shipper_name'] ?? 'Unknown Company');
        $salesUserId  = (int) ($result['sales_user_id'] ?? 0);
        $salesEmail   = trim((string) ($result['sales_email'] ?? ''));
        $isManagement = (int) ($result['is_management'] ?? 0);

        $targetEmail = $isManagement === 1
            ? 'cc@gateway-id.com'
            : $salesEmail;

        if ($targetEmail === '') {
            $targetEmail = 'overseas@gateway-id.com';
        }

        $origin      = trim((string) ($payload['origin'] ?? ''));
        $destination = trim((string) ($payload['destination'] ?? ''));

        if ($salesUserId > 0 &&
            $this->master_db->table_exists('crm_notifications')) {
            $this->master_db->insert('crm_notifications', [
                'user_id'    => $salesUserId,
                'title'      => 'New Quotation Request',
                'body'       => "New RFQ from {$shipperName} for route {$origin} to {$destination}",
                'link'       => '/app/quotations/requests/' . $requestId,
                'ref_type'   => 'QUOTE_REQUEST',
                'ref_id'     => $requestId,
                'severity'   => 'INFO',
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $apiKey = trim((string) (
            getenv('ELASTIC_EMAIL_API_KEY')
            ?: $this->config->item('elastic_email_api_key')
        ));

        if ($apiKey === '') {
            log_message(
                'error',
                'Quotation email skipped: ELASTIC_EMAIL_API_KEY not configured.'
            );
            return false;
        }

        $safe = static function ($value) {
            return htmlspecialchars(
                (string) $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        };

        $safeShipperName = $safe($shipperName);
        $safeOrigin      = $safe($origin);
        $safeDestination = $safe($destination);
        $transportType   = $safe(strtoupper((string) ($payload['transport_type'] ?? 'SEA')));
        $serviceType     = $safe(strtoupper((string) ($payload['service_type'] ?? '-')));
        $quantity        = $safe($payload['quantity'] ?? '-');
        $packaging       = $safe($payload['packaging'] ?? '');
        $weight          = $safe($payload['weight'] ?? '-');
        $volume          = $safe($payload['volume'] ?? '-');
        $readyDate       = $safe($payload['ready_date'] ?? '-');

        $htmlContent = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #0f4fd8;'>New Quotation Request (RFQ)</h2>
                <p>Halo, ada permintaan penawaran baru dari portal pelanggan.</p>
                <hr>
                <table border='0' cellpadding='5' cellspacing='0'>
                    <tr><td><strong>Shipper</strong></td><td>: {$safeShipperName}</td></tr>
                    <tr><td><strong>Transport Type</strong></td><td>: {$transportType}</td></tr>
                    <tr><td><strong>Route</strong></td><td>: {$safeOrigin} &rarr; {$safeDestination}</td></tr>
                    <tr><td><strong>Service</strong></td><td>: {$serviceType}</td></tr>
                    <tr><td><strong>Quantity</strong></td><td>: {$quantity} {$packaging}</td></tr>
                    <tr><td><strong>Weight/Vol</strong></td><td>: {$weight} KG / {$volume} CBM</td></tr>
                    <tr><td><strong>Ready Date</strong></td><td>: {$readyDate}</td></tr>
                </table>
                <br>
                <p>Mohon segera tindak lanjuti permintaan ini melalui sistem CRM.</p>
            </div>
        ";

        $emailPayload = [
            'Recipients' => [
                'To' => [$targetEmail],
            ],
            'Content' => [
                'Body' => [[
                    'ContentType' => 'HTML',
                    'Charset'     => 'utf-8',
                    'Content'     => $htmlContent,
                ]],
                'From'    => 'no-reply@gcl.digital',
                'Subject' => "RFQ Portal: {$shipperName} ({$origin})",
            ],
        ];

        $ch = curl_init('https://api.elasticemail.com/v4/emails/transactional');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($emailPayload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-ElasticEmail-ApiKey: ' . $apiKey,
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $responseBody = curl_exec($ch);
        $curlError    = curl_error($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
            log_message(
                'error',
                'Quotation email failed. HTTP=' . $httpCode .
                ' CURL=' . $curlError .
                ' BODY=' . substr((string) $responseBody, 0, 500)
            );
            return false;
        }

        return true;
    }
}
