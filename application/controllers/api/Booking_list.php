<?php
use chriskacerguis\RestServer\RestController;

class Booking_list extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('api/M_booking_list');
        $this->load->model('M_firmenich_kpi_report', 'Firmenich_kpi_report_model');
        $this->load->library('Customer_auth');
        $this->load->library('Gcl_redis_cache');
    }

    /**
     * GET /api/booking_list
     *
     * Optional filters:
     * - shipper_id=4407
     * - date_from=2026-08-01
     * - date_to=2026-08-31
     *
     * Security:
     * shipper_id dari query SELALU divalidasi terhadap mstshipper_user_access
     * melalui Customer_auth. User tidak bisa membaca shipper di luar aksesnya.
     */
    public function index_get()
    {
        $auth = $this->customer_auth->authenticate('booking.view');

        if (empty($auth['ok'])) {
            return $this->response([
                'status'  => false,
                'message' => $auth['message'] ?? 'Unauthorized',
            ], (int) ($auth['status'] ?? RestController::HTTP_UNAUTHORIZED));
        }

        $allowedShipperIds = $this->extractShipperIds($auth);

        if (empty($allowedShipperIds)) {
            return $this->response([
                'status'  => false,
                'message' => 'Tidak ada shipper aktif yang dapat diakses oleh user ini.',
            ], RestController::HTTP_FORBIDDEN);
        }

        $requestedShipperId = (int) ($this->input->get('shipper_id', true) ?: 0);
        $selectedShipperId = $this->resolveSelectedShipperId(
            $auth,
            $allowedShipperIds,
            $requestedShipperId
        );

        if ($selectedShipperId <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Shipper tidak termasuk dalam akses user ini.',
            ], RestController::HTTP_FORBIDDEN);
        }

        $dateFrom = trim((string) $this->input->get('date_from', true));
        $dateTo   = trim((string) $this->input->get('date_to', true));

        // Tanpa date filter = tampilkan seluruh periode.
        // Jika salah satu tanggal diisi, keduanya wajib diisi.
        if (($dateFrom === '') xor ($dateTo === '')) {
            return $this->response([
                'status'  => false,
                'message' => 'date_from dan date_to harus diisi keduanya, atau kosongkan keduanya untuk seluruh periode.',
            ], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            if (!$this->isValidYmd($dateFrom) || !$this->isValidYmd($dateTo)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Format date_from/date_to harus YYYY-MM-DD.',
                ], RestController::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($dateFrom > $dateTo) {
                return $this->response([
                    'status'  => false,
                    'message' => 'date_from tidak boleh lebih besar dari date_to.',
                ], RestController::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $hasDateFilter = ($dateFrom !== '' && $dateTo !== '');
        $dateFrom = $hasDateFilter ? $dateFrom : null;
        $dateTo   = $hasDateFilter ? $dateTo : null;

        $shipperIds = [$selectedShipperId];
        $firmenichIds = [4407, 6412];
        $isFirmenich = in_array($selectedShipperId, $firmenichIds, true);

        try {
            $cacheKey = $this->bookingCacheKey(
                $selectedShipperId,
                $dateFrom,
                $dateTo
            );

            $forceRefresh = ((int) ($this->input->get('refresh', true) ?: 0) === 1);
            $cacheAvailable = $this->gcl_redis_cache->isAvailable();
            $cacheState = $cacheAvailable ? 'MISS' : 'DISABLED';
            $data = null;

            // Auth + shipper authorization SELALU sudah dilakukan sebelum cache lookup.
            // Yang di-cache hanya business data untuk shipper + periode tersebut.
            if ($cacheAvailable && !$forceRefresh) {
                $cachedData = $this->gcl_redis_cache->get($cacheKey);
                if (is_array($cachedData)) {
                    $data = $cachedData;
                    $cacheState = 'HIT';
                }
            }

            if (!is_array($data)) {
                $data = $this->M_booking_list->get_combined_bookings(
                    $shipperIds,
                    $dateFrom,
                    $dateTo
                );

                // Frontend Firmenich membaca data.firmenich_rows.
                // Isi juga saat All Period (dateFrom/dateTo = null).
                $data['firmenich_rows'] = [];
                $data['kpi'] = null;
                $data['operational_kpi'] = null;

                if ($isFirmenich) {
                    $firmenich = $this->M_booking_list->get_firmenich_performance(
                        $selectedShipperId,
                        $dateFrom,
                        $dateTo
                    );

                    $data['firmenich_rows'] = is_array($firmenich['rows'] ?? null)
                        ? $firmenich['rows']
                        : [];
                    $data['kpi'] = is_array($firmenich['kpi'] ?? null)
                        ? $firmenich['kpi']
                        : null;

                    // Operational KPI memakai source/rule yang SAMA dengan
                    // Firmenich KPI Google Sheet (M_firmenich_kpi_report).
                    // Dengan begitu Booking Page dan spreadsheet tidak berbeda angka.
                    try {
                        $kpiRows = $this->Firmenich_kpi_report_model->get_rows_b_to_cy(
                            [$selectedShipperId],
                            $dateFrom,
                            $dateTo,
                            false
                        );

                        $operational = $this->mergeOperationalKpi(
                            $data['firmenich_rows'],
                            is_array($kpiRows) ? $kpiRows : []
                        );

                        $data['firmenich_rows'] = $operational['rows'];
                        $data['operational_kpi'] = $operational['summary'];
                    } catch (Throwable $kpiError) {
                        // Jangan gagalkan Booking Page hanya karena summary KPI gagal.
                        log_message(
                            'error',
                            'Booking_list Firmenich operational KPI error: ' . $kpiError->getMessage()
                        );
                    }
                }

                if ($cacheAvailable) {
                    $ttl = (int) $this->gcl_redis_cache->configValue('booking_list_ttl', 180);
                    if ($this->gcl_redis_cache->set($cacheKey, $data, $ttl)) {
                        $cacheState = $forceRefresh ? 'REFRESHED' : 'MISS_STORED';
                    }
                }
            }

            return $this->response([
                'status' => true,
                'data'   => $data,
                'meta'   => [
                    'selected_shipper_id' => $selectedShipperId,
                    'shipper_ids'         => $shipperIds,
                    'accessible_shippers' => $this->publicShipperAccess($auth['shipper_access'] ?? []),
                    'date_from'           => $dateFrom,
                    'date_to'             => $dateTo,
                    'all_period'          => !$hasDateFilter,
                    'is_firmenich'        => $isFirmenich,
                    'cache'               => [
                        'status' => $cacheState,
                        'key' => $cacheKey,
                        'ttl' => $cacheAvailable ? $this->gcl_redis_cache->ttl($cacheKey) : null,
                        'force_refresh' => $forceRefresh,
                    ],
                    'total_bookings'      => count($data['bookings'] ?? []),
                    'total_last_booking'  => count($data['last_bookings'] ?? []),
                    'total_firmenich_rows'=> count($data['firmenich_rows'] ?? []),
                ],
            ], RestController::HTTP_OK);
        } catch (Throwable $e) {
            log_message('error', 'Booking_list error: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Gagal mengambil daftar booking.',
            ], 500);
        }
    }

    /**
     * Merge B..CY KPI row ke row performance booking berdasarkan HBL.
     *
     * Percentage mengikuti KPI Tracker:
     * shipment meets KPI / total shipment pada filter aktif.
     *
     * evaluated + coverage_rate tetap dikirim sebagai audit ketika ada shipment
     * yang belum punya data lengkap.
     */
    private function mergeOperationalKpi(array $performanceRows, array $kpiRows)
    {
        $byHbl = [];

        foreach ($kpiRows as $kpiRow) {
            $hbl = $this->normalizeHblKey($kpiRow['F'] ?? null);
            if ($hbl === '') {
                continue;
            }

            $byHbl[$hbl] = $kpiRow;
        }

        $merged = [];

        foreach ($performanceRows as $row) {
            $hbl = $this->normalizeHblKey($row['hbl'] ?? null);
            $kpiRow = ($hbl !== '' && isset($byHbl[$hbl]))
                ? $byHbl[$hbl]
                : [];

            $dgStatus = strtoupper(trim((string) ($kpiRow['S'] ?? '')));
            if ($dgStatus !== 'DG' && $dgStatus !== 'NDG') {
                $dgStatus = $this->deriveDgStatus($row['cargo_type'] ?? null);
            }

            $row['dg_status'] = $dgStatus;

            $row['kpi_departure'] = $this->nullableBool($kpiRow, 'CN');
            $row['kpi_arrival'] = $this->nullableBool($kpiRow, 'CS');
            $row['kpi_leadtime'] = $this->nullableBool($kpiRow, 'CW');
            $row['kpi_final_bl'] = $this->nullableBool($kpiRow, 'CT');
            $row['kpi_booking_confirmation'] = $this->nullableBool($kpiRow, 'CX');

            // Audit values untuk tooltip / future detail UI.
            $row['kpi_etd'] = $kpiRow['CM'] ?? null;
            $row['kpi_atd'] = $kpiRow['CL'] ?? null;
            $row['kpi_eta'] = $kpiRow['CR'] ?? null;
            $row['kpi_ata'] = $kpiRow['CQ'] ?? null;
            $row['kpi_actual_leadtime_days'] = $kpiRow['CU'] ?? null;
            $row['kpi_leadtime_difference_days'] = $kpiRow['CV'] ?? null;

            $merged[] = $row;
        }

        return [
            'rows' => $merged,
            'summary' => $this->buildOperationalKpiSummary($merged),
        ];
    }

    private function buildOperationalKpiSummary(array $rows)
    {
        $total = count($rows);

        $definitions = [
            'departure_reliability' => [
                'field' => 'kpi_departure',
                'label' => 'Departure Reliability',
                'target' => 85.0,
            ],
            'arrival_reliability' => [
                'field' => 'kpi_arrival',
                'label' => 'Arrival Reliability',
                'target' => 95.0,
            ],
            'leadtime_adherence' => [
                'field' => 'kpi_leadtime',
                'label' => 'Leadtime Adherence',
                'target' => 90.0,
            ],
            'documentation_final_bl' => [
                'field' => 'kpi_final_bl',
                'label' => 'Documentation Final BL',
                'target' => 90.0,
            ],
            'booking_confirmation_responsiveness' => [
                'field' => 'kpi_booking_confirmation',
                'label' => 'Booking Confirmation Responsiveness',
                'target' => 95.0,
            ],
        ];

        $summary = [
            'total_shipments' => $total,
        ];

        foreach ($definitions as $key => $definition) {
            $evaluated = 0;
            $meets = 0;

            foreach ($rows as $row) {
                $value = $row[$definition['field']] ?? null;

                if ($value === null) {
                    continue;
                }

                $evaluated++;

                if ($value === true || $value === 1 || $value === '1') {
                    $meets++;
                }
            }

            // KPI Tracker denominator = semua shipment pada filter.
            $percentage = $total > 0
                ? round(($meets / $total) * 100, 2)
                : 0.0;

            // Tambahan audit: performa hanya terhadap row yang evaluable.
            $evaluatedPercentage = $evaluated > 0
                ? round(($meets / $evaluated) * 100, 2)
                : null;

            $summary[$key] = [
                'label' => $definition['label'],
                'target' => $definition['target'],
                'meets' => $meets,
                'evaluated' => $evaluated,
                'missing' => max(0, $total - $evaluated),
                'total' => $total,
                'percentage' => $percentage,
                'evaluated_percentage' => $evaluatedPercentage,
                'coverage_rate' => $total > 0
                    ? round(($evaluated / $total) * 100, 2)
                    : 0.0,
            ];
        }

        $dg = 0;
        $ndg = 0;

        foreach ($rows as $row) {
            $status = strtoupper(trim((string) ($row['dg_status'] ?? '')));
            if ($status === 'DG') {
                $dg++;
            } elseif ($status === 'NDG') {
                $ndg++;
            }
        }

        $summary['dg_breakdown'] = [
            'dg' => $dg,
            'ndg' => $ndg,
            'total' => $total,
        ];

        return $summary;
    }

    private function nullableBool(array $row, $key)
    {
        if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return (bool) $row[$key];
    }

    private function deriveDgStatus($cargoType)
    {
        $cargo = strtoupper(trim((string) $cargoType));

        if (
            strpos($cargo, 'DG') !== false
            || strpos($cargo, 'DANGEROUS') !== false
            || strpos($cargo, 'HAZ') !== false
        ) {
            return 'DG';
        }

        return 'NDG';
    }

    private function normalizeHblKey($value)
    {
        return strtoupper(trim((string) $value));
    }

    private function bookingCacheKey($shipperId, $dateFrom, $dateTo)
    {
        $period = ($dateFrom && $dateTo)
            ? $dateFrom.':'.$dateTo
            : 'ALL';

        // v2 karena payload mencakup operational KPI + DG/NDG.
        return 'booking:list:v2:shipper:'.(int) $shipperId.':'.$period;
    }

    private function resolveSelectedShipperId(array $auth, array $allowed, $requested)
    {
        $requested = (int) $requested;

        if ($requested > 0) {
            return in_array($requested, $allowed, true) ? $requested : 0;
        }

        $defaultCandidates = [
            $auth['user']['id_shipper'] ?? null,
            $auth['default_shipper_id'] ?? null,
            $auth['id_shipper'] ?? null,
        ];

        foreach ($defaultCandidates as $candidate) {
            $candidate = (int) $candidate;
            if ($candidate > 0 && in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return (int) ($allowed[0] ?? 0);
    }

    private function publicShipperAccess(array $rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id_shipper'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $result[] = [
                'id_shipper'   => $id,
                'shipper_code' => (string) ($row['shipper_code'] ?? ''),
                'shipper_name' => (string) ($row['shipper_name'] ?? ''),
                'region_id'    => (string) ($row['region_id'] ?? ''),
                'is_default'   => (bool) ($row['is_default'] ?? false),
            ];
        }

        return $result;
    }

    private function isValidYmd($value)
    {
        $dt = DateTime::createFromFormat('!Y-m-d', (string) $value);
        $errors = DateTime::getLastErrors();

        return $dt instanceof DateTime
            && ($errors === false || (
                (int) $errors['warning_count'] === 0
                && (int) $errors['error_count'] === 0
            ))
            && $dt->format('Y-m-d') === $value;
    }

    /**
     * Mengambil daftar id_shipper dari hasil Customer_auth.
     */
    private function extractShipperIds(array $auth)
    {
        $rawIds = [];

        if (!empty($auth['shipper_ids']) && is_array($auth['shipper_ids'])) {
            $rawIds = $auth['shipper_ids'];
        }

        $fallbackIds = [
            $auth['id_shipper'] ?? null,
            $auth['default_shipper_id'] ?? null,
            $auth['user']['id_shipper'] ?? null,
            $auth['data']['id_shipper'] ?? null,
        ];

        foreach ($fallbackIds as $id) {
            if ($id !== null && $id !== '') {
                $rawIds[] = $id;
            }
        }

        $ids = [];
        foreach ($rawIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}
