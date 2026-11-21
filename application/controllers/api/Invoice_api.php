<?php
use chriskacerguis\RestServer\RestController;

class Invoice_api extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('api/M_invoice_api');
        $this->load->library('Customer_auth');
    }

    /**
     * GET /api/invoice_api
     * Optional: shipper_id, date_from, date_to (YYYY-MM-DD).
     * Filter tanggal berdasarkan tanggal invoice (x_finance.date), bukan ETD.
     */
    public function index_get()
    {
        $auth = $this->customer_auth->authenticate('invoice.view');

        if (empty($auth['ok'])) {
            return $this->response([
                'status'  => false,
                'message' => $auth['message'] ?? 'Unauthorized.',
            ], (int) ($auth['status'] ?? RestController::HTTP_UNAUTHORIZED));
        }

        $allowedShipperIds = $this->normalizeShipperIds($auth['shipper_ids'] ?? []);
        if (empty($allowedShipperIds)) {
            return $this->response([
                'status'  => false,
                'message' => 'Tidak ada shipper aktif yang dapat diakses oleh user.',
            ], RestController::HTTP_FORBIDDEN);
        }

        $requestedShipperId = (int) $this->input->get('shipper_id', true);
        $shipperIds = $allowedShipperIds;
        if ($requestedShipperId > 0) {
            if (!in_array($requestedShipperId, $allowedShipperIds, true)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Shipper tidak termasuk dalam akses user.',
                ], RestController::HTTP_FORBIDDEN);
            }
            $shipperIds = [$requestedShipperId];
        }
        sort($shipperIds, SORT_NUMERIC);

        $dateFrom = trim((string) ($this->input->get('date_from', true) ?? ''));
        $dateTo   = trim((string) ($this->input->get('date_to', true) ?? ''));
        if (($dateFrom !== '' && !$this->isValidDate($dateFrom)) ||
            ($dateTo !== '' && !$this->isValidDate($dateTo)) ||
            ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo)) {
            return $this->response([
                'status'  => false,
                'message' => 'Filter tanggal tidak valid. Gunakan YYYY-MM-DD dan pastikan tanggal mulai tidak melewati tanggal akhir.',
            ], RestController::HTTP_BAD_REQUEST);
        }

        // Batas rentang tahun untuk mencegah query tidak disengaja ke terlalu banyak database.
        if ($dateFrom !== '' && $dateTo !== '' &&
            ((int) substr($dateTo, 0, 4) - (int) substr($dateFrom, 0, 4)) > 5) {
            return $this->response([
                'status'  => false,
                'message' => 'Rentang tanggal maksimal 6 tahun kalender.',
            ], RestController::HTTP_BAD_REQUEST);
        }

        try {
            // v5: pisahkan cache dari format lama karena menambah type_of_trans.
            $cacheKey = 'invoice_list_v5_lcl_' . sha1(implode(',', $shipperIds) . '|' . $dateFrom . '|' . $dateTo);
            $response = $this->readCacheSafely($cacheKey);
            if ($response === false) {
                $response = $this->M_invoice_api->get_list_invoice($shipperIds, $dateFrom, $dateTo);
                $this->writeCacheSafely($cacheKey, $response, 300);
            }

            $invoiceRows = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
            $totalsByCurrency = [];
            foreach ($invoiceRows as $invoice) {
                if (($invoice['grand_total'] ?? null) === null || $invoice['grand_total'] === '') {
                    continue; // Nominal tidak ada: jangan dianggap nol.
                }
                $currency = strtoupper(trim((string) ($invoice['currency'] ?? '')));
                if ($currency === '') {
                    $currency = 'N/A'; // Jangan menjumlahkan currency yang tidak diketahui ke IDR.
                }
                if (!isset($totalsByCurrency[$currency])) {
                    $totalsByCurrency[$currency] = 0.0;
                }
                $totalsByCurrency[$currency] += (float) $invoice['grand_total'];
            }
            ksort($totalsByCurrency);

            return $this->response([
                'status' => true,
                'data'   => $response,
                'meta'   => [
                    'shipper_ids'        => $shipperIds,
                    'allowed_shipper_ids' => $allowedShipperIds,
                    'date_from'          => $dateFrom ?: null,
                    'date_to'            => $dateTo ?: null,
                    'total_invoice'      => count($invoiceRows),
                    'totals_by_currency' => $totalsByCurrency,
                ],
            ], RestController::HTTP_OK);
        } catch (Throwable $e) {
            log_message('error', 'Invoice_api index_get error: ' . $e->getMessage());
            return $this->response([
                'status'  => false,
                'message' => 'Gagal mengambil daftar invoice. Silakan hubungi administrator.',
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function isValidDate($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }

    private function normalizeShipperIds($shipperIds)
    {
        if (!is_array($shipperIds)) {
            $shipperIds = [$shipperIds];
        }

        $normalized = [];
        foreach ($shipperIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * Cache tidak boleh membuat endpoint invoice gagal ketika Redis bermasalah.
     */
    private function readCacheSafely($cacheKey)
    {
        try {
            $this->load->driver('cache', [
                'adapter' => 'redis',
                'backup'  => 'file',
            ]);

            return $this->cache->get($cacheKey);
        } catch (Throwable $e) {
            log_message('error', 'Invoice cache read skipped: ' . $e->getMessage());
            return false;
        }
    }

    private function writeCacheSafely($cacheKey, $value, $ttl)
    {
        try {
            if (isset($this->cache)) {
                $this->cache->save($cacheKey, $value, (int) $ttl);
            }
        } catch (Throwable $e) {
            log_message('error', 'Invoice cache write skipped: ' . $e->getMessage());
        }
    }
}
