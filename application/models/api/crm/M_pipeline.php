<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class M_pipeline extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    public function __construct()
    {
        parent::__construct();
        // Sesuaikan dengan konfigurasi Anda (master/2026/etc). Untuk saat ini tetap 'master'
        $this->dbx = $this->load->database('master', true);
    }

    /* =========================
     * Helpers
     * ========================= */

    public function sanitize_ymd($s, $fallback)
    {
        $t = trim((string)$s);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) return $t;
        return (string)$fallback;
    }

    private function isAll($v)
    {
        $t = strtoupper(trim((string)$v));
        return ($t === '' || $t === 'ALL');
    }

    private function nowJakarta()
    {
        // Tidak mengubah timezone global server.
        $dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        return $dt->format('Y-m-d H:i:s');
    }

    private function asJakartaDateTime($ymd, $hms)
    {
        $dt = new DateTime($ymd . ' ' . $hms, new DateTimeZone('Asia/Jakarta'));
        return $dt->format('Y-m-d H:i:s');
    }

    private function bucketFlags($dueAt, $asOfYmd)
    {
        // Return bool flags for UI.
        if (!$dueAt || $dueAt === '0000-00-00 00:00:00') {
            return ['is_overdue'=>false,'is_due_today'=>false,'is_next_7d'=>false];
        }
        $dueDate = substr((string)$dueAt, 0, 10);
        if ($dueDate < $asOfYmd) {
            return ['is_overdue'=>true,'is_due_today'=>false,'is_next_7d'=>false];
        }
        if ($dueDate === $asOfYmd) {
            return ['is_overdue'=>false,'is_due_today'=>true,'is_next_7d'=>false];
        }
        $next7 = date('Y-m-d', strtotime($asOfYmd . ' +7 day'));
        if ($dueDate > $asOfYmd && $dueDate <= $next7) {
            return ['is_overdue'=>false,'is_due_today'=>false,'is_next_7d'=>true];
        }
        return ['is_overdue'=>false,'is_due_today'=>false,'is_next_7d'=>false];
    }

    /* =========================
     * Public API
     * ========================= */

    public function get_today($region, $sales, $dateYmd)
    {
        $tz = 'Asia/Jakarta';

        $dateYmd = $this->sanitize_ymd($dateYmd, date('Y-m-d'));
        $todayStart = $this->asJakartaDateTime($dateYmd, '00:00:00');
        $todayEnd   = $this->asJakartaDateTime($dateYmd, '23:59:59');
        $next7End   = $this->asJakartaDateTime(date('Y-m-d', strtotime($dateYmd . ' +7 day')), '23:59:59');

        // Snapshot rule for historical navigation:
        // choose the latest activity per entity *up to end-of-day as_of*.
        $cutoffCreatedAtEnd = $todayEnd;

        $leadRows = $this->get_followups('lead', $region, $sales, $dateYmd, $next7End, $cutoffCreatedAtEnd);
        $custRows = $this->get_followups('customer', $region, $sales, $dateYmd, $next7End, $cutoffCreatedAtEnd);
        $oppRows  = $this->get_followups('opportunity', $region, $sales, $dateYmd, $next7End, $cutoffCreatedAtEnd);

        $feed = $this->get_activity_feed($region, $sales, $todayStart, $todayEnd);

        return [
            'status' => true,
            'message' => 'OK',
            'meta' => [
                'date' => $dateYmd,
                'timezone' => $tz,
                'server_time' => $this->nowJakarta(),
                'region' => $region,
                'sales' => $sales,
            ],
            'summary' => [
                'leads' => $this->summarize_followups($leadRows, $dateYmd),
                'customers' => $this->summarize_followups($custRows, $dateYmd),
                'opportunities' => $this->summarize_followups($oppRows, $dateYmd),
            ],
            'queues' => [
                'lead_followups' => $leadRows,
                'customer_followups' => $custRows,
                'tasks' => [],
                'quotations' => [],
                'opportunities' => $oppRows,
            ],
            'activity_feed' => $feed,
        ];
    }

    /**
     * History series: summary per day within [from..to]
     * Snapshot per day: latest activity per entity up to end-of-day date.
     */
    public function get_history_series($region, $sales, $fromYmd, $toYmd)
    {
        $fromYmd = $this->sanitize_ymd($fromYmd, date('Y-m-d', strtotime('-14 days')));
        $toYmd   = $this->sanitize_ymd($toYmd, date('Y-m-d'));
        if ($fromYmd > $toYmd) { $tmp = $fromYmd; $fromYmd = $toYmd; $toYmd = $tmp; }

        // Soft limit: avoid very wide ranges by default (protect API)
        $maxDays = 120;
        $days = (int)((strtotime($toYmd) - strtotime($fromYmd)) / 86400) + 1;
        if ($days > $maxDays) {
            $fromYmd = date('Y-m-d', strtotime($toYmd . ' -' . ($maxDays-1) . ' day'));
        }

        $series = [];
        $cursor = $fromYmd;
        while ($cursor <= $toYmd) {
            $series[] = [
                'date' => $cursor,
                'leads' => $this->count_bucket_for_date('lead', $region, $sales, $cursor),
                'customers' => $this->count_bucket_for_date('customer', $region, $sales, $cursor),
                'opportunities' => $this->count_bucket_for_date('opportunity', $region, $sales, $cursor),
            ];
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        return [
            'status' => true,
            'message' => 'OK',
            'meta' => [
                'from' => $fromYmd,
                'to' => $toYmd,
                'timezone' => 'Asia/Jakarta',
                'server_time' => $this->nowJakarta(),
                'region' => $region,
                'sales' => $sales,
            ],
            'series' => $series,
        ];
    }

    /**
     * Detail bucket for click (overdue/due_today/next7d)
     * Snapshot: latest activity per entity up to end-of-day as_of.
     */
    public function get_bucket_details(array $p)
    {
        $entityType = strtolower(trim((string)($p['entity_type'] ?? '')));
        $bucket     = strtolower(trim((string)($p['bucket'] ?? '')));
        $asOfYmd    = $this->sanitize_ymd($p['as_of'] ?? '', date('Y-m-d'));
        $region     = (string)($p['region'] ?? 'ALL');
        $sales      = (string)($p['sales'] ?? 'ALL');
        $page       = (int)($p['page'] ?? 1);
        $limit      = (int)($p['limit'] ?? 50);
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 1;
        if ($limit > 200) $limit = 200;
        $offset = ($page - 1) * $limit;

        $allowedEntity = ['lead','customer','opportunity'];
        $allowedBucket = ['overdue','due_today','next7d'];
        if (!in_array($entityType, $allowedEntity, true)) {
            return ['status'=>false,'message'=>'Invalid entity_type'];
        }
        if (!in_array($bucket, $allowedBucket, true)) {
            return ['status'=>false,'message'=>'Invalid bucket'];
        }

        // Snapshot: latest activity per entity up to end-of-day as_of
        $cutoffCreatedAtEnd = $this->asJakartaDateTime($asOfYmd, '23:59:59');

        // Build bucket WHERE clause (on DATE(next_action_due_at))
        $next7Ymd = date('Y-m-d', strtotime($asOfYmd . ' +7 day'));
        $bucketSql = '';
        $bucketArgs = [];
        if ($bucket === 'overdue') {
            $bucketSql = 'DATE(a.next_action_due_at) < ?';
            $bucketArgs = [$asOfYmd];
        } elseif ($bucket === 'due_today') {
            $bucketSql = 'DATE(a.next_action_due_at) = ?';
            $bucketArgs = [$asOfYmd];
        } else {
            $bucketSql = 'DATE(a.next_action_due_at) > ? AND DATE(a.next_action_due_at) <= ?';
            $bucketArgs = [$asOfYmd, $next7Ymd];
        }

        // Subquery: latest created_at per entity (current next action)
        $whereRegion = $this->isAll($region) ? '' : ' AND region_id = ? ';
        $whereSales  = $this->isAll($sales)  ? '' : ' AND owner_sales_id = ? ';

        $sqlBase = "
            FROM crm_activities a
            JOIN (
                SELECT entity_id, MAX(created_at) AS mx
                FROM crm_activities
                WHERE entity_type = ?
                  AND deleted_at IS NULL
                  AND next_action_due_at IS NOT NULL
                  AND created_at <= ?
                  $whereRegion
                  $whereSales
                GROUP BY entity_id
            ) t ON t.entity_id = a.entity_id AND t.mx = a.created_at
            WHERE a.entity_type = ?
              AND a.deleted_at IS NULL
              AND a.next_action_due_at IS NOT NULL
              AND a.created_at <= ?
              " . ($this->isAll($region) ? '' : ' AND a.region_id = ? ') . "
              " . ($this->isAll($sales) ? '' : ' AND a.owner_sales_id = ? ') . "
              AND ($bucketSql)
        ";

        // Args builder
        $args = [$entityType, $cutoffCreatedAtEnd];
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;
        $args[] = $entityType;
        $args[] = $cutoffCreatedAtEnd;
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;
        $args = array_merge($args, $bucketArgs);

        // Total
        $totalSql = 'SELECT COUNT(1) AS cnt ' . $sqlBase;
        $totalRow = $this->dbx->query($totalSql, $args)->row_array();
        $total = (int)($totalRow['cnt'] ?? 0);

        // Page rows
        $rowsSql = "SELECT a.* " . $sqlBase . " ORDER BY a.next_action_due_at ASC, a.created_at DESC LIMIT ? OFFSET ?";
        $pageArgs = array_merge($args, [$limit, $offset]);
        $actRows = $this->dbx->query($rowsSql, $pageArgs)->result_array();

        $queueItems = $this->build_queue_items_from_activities($entityType, $actRows, $asOfYmd);

        return [
            'status' => true,
            'message' => 'OK',
            'meta' => [
                'entity_type' => $entityType,
                'bucket' => $bucket,
                'as_of' => $asOfYmd,
                'timezone' => 'Asia/Jakarta',
                'server_time' => $this->nowJakarta(),
                'region' => $region,
                'sales' => $sales,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
            'rows' => $queueItems,
        ];
    }

    /**
    /**
 * Timeline activities for one entity
 */
public function activity_timeline(array $p)
{
    $entityType = strtolower(trim((string)($p['entity_type'] ?? '')));
    $entityId   = (int)($p['entity_id'] ?? 0);
    $fromYmd    = $this->sanitize_ymd($p['from'] ?? '', date('Y-m-d', strtotime('-30 days')));
    $toYmd      = $this->sanitize_ymd($p['to'] ?? '', date('Y-m-d'));
    if ($fromYmd > $toYmd) { $tmp = $fromYmd; $fromYmd = $toYmd; $toYmd = $tmp; }

    $region = (string)($p['region'] ?? 'ALL');
    $sales  = (string)($p['sales'] ?? 'ALL');
    $page   = (int)($p['page'] ?? 1);
    $limit  = (int)($p['limit'] ?? 50);
    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 1;
    if ($limit > 200) $limit = 200;
    $offset = ($page - 1) * $limit;

    $start = $fromYmd . ' 00:00:00';
    $end   = $toYmd . ' 23:59:59';

    $qb = $this->dbx->from('crm_activities')
        ->where('deleted_at IS NULL', null, false)
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->where('created_at >=', $start)
        ->where('created_at <=', $end);

    if (!$this->isAll($region)) $qb->where('region_id', $region);
    if (!$this->isAll($sales))  $qb->where('owner_sales_id', (int)$sales);

    $total = (int)$qb->count_all_results('', false);

    $rows = $qb->order_by('created_at', 'DESC')
        ->limit($limit, $offset)
        ->get()
        ->result_array();

    // tambahkan attachments per activity
    foreach ($rows as &$row) {
        $row['attachments'] = $this->dbx
            ->from('crm_activity_attachments')
            ->where('activity_id', $row['activity_id'])
            ->get()
            ->result_array();
    }

    return [
        'status' => true,
        'message' => 'OK',
        'meta' => [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from' => $fromYmd,
            'to' => $toYmd,
            'timezone' => 'Asia/Jakarta',
            'server_time' => $this->nowJakarta(),
            'region' => $region,
            'sales' => $sales,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ],
        'rows' => $rows,
    ];
}
    /* =========================
     * Internals
     * ========================= */

    private function summarize_followups(array $rows, $asOfYmd)
    {
        $overdue = 0; $dueToday = 0; $next7 = 0;
        foreach ($rows as $r) {
            $due = $r['next_action']['due_at'] ?? null;
            if (!$due) continue;
            $dueDate = substr((string)$due, 0, 10);
            if ($dueDate < $asOfYmd) $overdue++;
            else if ($dueDate === $asOfYmd) $dueToday++;
            else $next7++;
        }
        return [
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'scheduled_next_7d' => $next7,
        ];
    }

    /**
     * Count buckets for a given date (for history series).
     * Buckets are computed from snapshot (latest activity up to end-of-day).
     */
    private function count_bucket_for_date($entityType, $region, $sales, $asOfYmd)
    {
        $next7Ymd = date('Y-m-d', strtotime($asOfYmd . ' +7 day'));
        $cutoffCreatedAtEnd = $this->asJakartaDateTime($asOfYmd, '23:59:59');

        $whereRegion = $this->isAll($region) ? '' : ' AND region_id = ? ';
        $whereSales  = $this->isAll($sales)  ? '' : ' AND owner_sales_id = ? ';

        $sql = "
            SELECT
                SUM(CASE WHEN DATE(a.next_action_due_at) <  ? THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN DATE(a.next_action_due_at) =  ? THEN 1 ELSE 0 END) AS due_today,
                SUM(CASE WHEN DATE(a.next_action_due_at) >  ? AND DATE(a.next_action_due_at) <= ? THEN 1 ELSE 0 END) AS next7d
            FROM crm_activities a
            JOIN (
                SELECT entity_id, MAX(created_at) AS mx
                FROM crm_activities
                WHERE entity_type = ?
                  AND deleted_at IS NULL
                  AND next_action_due_at IS NOT NULL
                  AND created_at <= ?
                  $whereRegion
                  $whereSales
                GROUP BY entity_id
            ) t ON t.entity_id = a.entity_id AND t.mx = a.created_at
            WHERE a.entity_type = ?
              AND a.deleted_at IS NULL
              AND a.next_action_due_at IS NOT NULL
              AND a.created_at <= ?
              " . ($this->isAll($region) ? '' : ' AND a.region_id = ? ') . "
              " . ($this->isAll($sales) ? '' : ' AND a.owner_sales_id = ? ') . "
        ";

        $args = [$asOfYmd, $asOfYmd, $asOfYmd, $next7Ymd, $entityType, $cutoffCreatedAtEnd];
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;
        $args[] = $entityType;
        $args[] = $cutoffCreatedAtEnd;
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;

        $row = $this->dbx->query($sql, $args)->row_array();
        return [
            'overdue' => (int)($row['overdue'] ?? 0),
            'due_today' => (int)($row['due_today'] ?? 0),
            'scheduled_next_7d' => (int)($row['next7d'] ?? 0),
        ];
    }

    /**
     * Fetch followup queue items for one entity type.
     * We take latest activity per entity (snapshot up to cutoffCreatedAtEnd) that has next_action_due_at <= maxDueAt.
     */
    private function get_followups($entityType, $region, $sales, $asOfYmd, $maxDueAt, $cutoffCreatedAtEnd)
    {
        $entityType = strtolower(trim((string)$entityType));
        $sales = (string)$sales;
        $region = (string)$region;

        $whereRegion = $this->isAll($region) ? '' : ' AND region_id = ? ';
        $whereSales  = $this->isAll($sales)  ? '' : ' AND owner_sales_id = ? ';

        $sql = "
            SELECT a.*
            FROM crm_activities a
            JOIN (
                SELECT entity_id, MAX(created_at) AS mx
                FROM crm_activities
                WHERE entity_type = ?
                  AND deleted_at IS NULL
                  AND next_action_due_at IS NOT NULL
                  AND created_at <= ?
                  $whereRegion
                  $whereSales
                GROUP BY entity_id
            ) t ON t.entity_id = a.entity_id AND t.mx = a.created_at
            WHERE a.entity_type = ?
              AND a.deleted_at IS NULL
              AND a.next_action_due_at IS NOT NULL
              AND a.created_at <= ?
              " . ($this->isAll($region) ? '' : ' AND a.region_id = ? ') . "
              " . ($this->isAll($sales) ? '' : ' AND a.owner_sales_id = ? ') . "
              AND a.next_action_due_at <= ?
            ORDER BY a.next_action_due_at ASC, a.created_at DESC
        ";

        $args = [$entityType, $cutoffCreatedAtEnd];
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;
        $args[] = $entityType;
        $args[] = $cutoffCreatedAtEnd;
        if (!$this->isAll($region)) $args[] = $region;
        if (!$this->isAll($sales))  $args[] = (int)$sales;
        $args[] = $maxDueAt;

        $actRows = $this->dbx->query($sql, $args)->result_array();
        return $this->build_queue_items_from_activities($entityType, $actRows, $asOfYmd);
    }

    /**
     * Build queue cards from latest-activity rows.
     */
    private function build_queue_items_from_activities($entityType, array $actRows, $asOfYmd)
    {
        if (!$actRows) return [];

        $ids = [];
        foreach ($actRows as $a) {
            $ids[] = (int)$a['entity_id'];
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) return [];

        $entityType = strtolower($entityType);
        $masterMap = $this->load_master_map($entityType, $ids);

        $out = [];
        foreach ($actRows as $a) {
            $eid = (int)$a['entity_id'];
            $m = $masterMap[$eid] ?? null;
            if (!$m) continue;

            $dueAt = $a['next_action_due_at'] ?? null;
            $flags = $this->bucketFlags($dueAt, $asOfYmd);

            if ($entityType === 'lead') {
                $out[] = [
                    'entity_type' => 'lead',
                    'lead_id' => (int)$m['id_lead'],
                    'lead_code' => (string)$m['lead_code'],
                    'company_name' => (string)$m['company_name'],
                    'contact' => [
                        'name' => $m['contact_name'] ?? null,
                        'email' => $m['contact_email'] ?? null,
                        'phone' => $m['contact_phone'] ?? null,
                        'position' => $m['position'] ?? null,
                    ],
                    'status' => (string)($m['status'] ?? ''),
                    'probability' => (int)($m['probability'] ?? 0),
                    'owner' => [
                        'sales_id' => (int)($a['owner_sales_id'] ?? 0),
                        'region_id' => (string)($a['region_id'] ?? ''),
                    ],
                    'last_activity_at' => $a['created_at'] ?? null,
                    'next_action' => [
                        'type' => $a['next_action_type'] ?? null,
                        'due_at' => $dueAt ?: null,
                        'note' => $a['next_action_note'] ?? null,
                    ],
                    'ui' => [
                        'is_overdue' => (bool)$flags['is_overdue'],
                        'is_due_today' => (bool)$flags['is_due_today'],
                        'is_scheduled_next_7d' => (bool)$flags['is_next_7d'],
                        'links' => [
                            'detail' => '/crm/accounts/leads/' . (int)$m['id_lead'],
                        ],
                    ],
                ];
                continue;
            }

            if ($entityType === 'customer') {
                $out[] = [
                    'entity_type' => 'customer',
                    'id_shipper' => (int)$m['id_shipper'],
                    'shipper_name' => (string)$m['shipper_name'],
                    'cp' => [
                        'name' => $m['cp_name'] ?? null,
                        'email' => $m['cp_email'] ?? null,
                        'phone' => $m['cp_phone'] ?? null,
                    ],
                    'owner' => [
                        'sales_id' => (int)($a['owner_sales_id'] ?? 0),
                        'region_id' => (string)($a['region_id'] ?? ''),
                    ],
                    'last_activity_at' => $a['created_at'] ?? null,
                    'next_action' => [
                        'type' => $a['next_action_type'] ?? null,
                        'due_at' => $dueAt ?: null,
                        'note' => $a['next_action_note'] ?? null,
                    ],
                    'ui' => [
                        'is_overdue' => (bool)$flags['is_overdue'],
                        'is_due_today' => (bool)$flags['is_due_today'],
                        'is_scheduled_next_7d' => (bool)$flags['is_next_7d'],
                        'links' => [
                            'detail' => '/crm/accounts/customers/' . (int)$m['id_shipper'],
                        ],
                    ],
                ];
                continue;
            }

            // opportunity
            $service = trim(implode(' • ', array_filter([
                $m['shipment_type'] ?? null,
                isset($m['transport_mode']) ? ((string)$m['transport_mode'] . (!empty($m['sea_type']) ? '/' . (string)$m['sea_type'] : '')) : null,
                $m['service_level'] ?? null,
            ])));
            $route = trim(implode(' → ', array_filter([
                $m['origin'] ?? null,
                $m['destination'] ?? null,
            ])));

            $out[] = [
                'entity_type' => 'opportunity',
                'opportunity_id' => (int)$m['opportunity_id'],
                'account_name' => (string)($m['account_name'] ?? ''),
                'stage' => (string)($m['stage'] ?? ''),
                'source_type' => $m['source_type'] ?? null,
                'lead_id' => $m['lead_id'] ?? null,
                'id_shipper' => $m['id_shipper'] ?? null,
                'service' => $service,
                'route' => $route,
                'owner' => [
                    'sales_id' => (int)($a['owner_sales_id'] ?? 0),
                    'region_id' => (string)($a['region_id'] ?? ''),
                ],
                'last_activity_at' => $a['created_at'] ?? null,
                'next_action' => [
                    'type' => $a['next_action_type'] ?? null,
                    'due_at' => $dueAt ?: null,
                    'note' => $a['next_action_note'] ?? null,
                ],
                'ui' => [
                    'is_overdue' => (bool)$flags['is_overdue'],
                    'is_due_today' => (bool)$flags['is_due_today'],
                    'is_scheduled_next_7d' => (bool)$flags['is_next_7d'],
                    'links' => [
                        'detail' => '/crm/sales/opportunities/' . (int)$m['opportunity_id'],
                    ],
                ],
            ];
        }

        return $out;
    }

    private function load_master_map($entityType, array $ids)
    {
        $entityType = strtolower($entityType);
        $map = [];

        if ($entityType === 'lead') {
            $rows = $this->dbx
                ->from('crm_leads')
                ->where_in('id_lead', $ids)
                ->where('deleted_at IS NULL', null, false)
                ->get()
                ->result_array();
            foreach ($rows as $r) {
                $map[(int)$r['id_lead']] = $r;
            }
            return $map;
        }

        if ($entityType === 'customer') {
            $rows = $this->dbx
                ->from('mstshipper')
                ->where_in('id_shipper', $ids)
                ->get()
                ->result_array();
            foreach ($rows as $r) {
                $map[(int)$r['id_shipper']] = $r;
            }
            return $map;
        }

        // opportunity
        $rows = $this->dbx
            ->from('crm_opportunities')
            ->where_in('opportunity_id', $ids)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->result_array();
        foreach ($rows as $r) {
            $map[(int)$r['opportunity_id']] = $r;
        }
        return $map;
    }

    /**
     * Activity feed for a given datetime window.
     */
    private function get_activity_feed($region, $sales, $startDt, $endDt)
{
    $sales  = (string)$sales;
    $region = (string)$region;

    $qb = $this->dbx->from('crm_activities a');
    $qb->join('crm_leads l', "a.entity_type='lead' AND a.entity_id=l.id_lead", 'left', false);
    $qb->join('mstshipper s', "a.entity_type='customer' AND a.entity_id=s.id_shipper", 'left', false);
    $qb->join('crm_opportunities o', "a.entity_type='opportunity' AND a.entity_id=o.opportunity_id", 'left', false);

    $qb->select("
        a.activity_id,
        a.entity_type,
        a.entity_id,
        a.region_id,
        a.owner_sales_id,
        a.action_type,
        a.channel,
        a.subject,
        a.note,
        a.outcome,
        a.attachment_count,
        a.created_by_sales_id,
        a.created_at,
        a.activity_at,
        a.next_action_type,
        a.next_action_due_at,
        a.next_action_note,
        o.source_type AS opportunity_source_type,
        o.lead_id AS opportunity_lead_id,
        o.id_shipper AS opportunity_id_shipper,
        COALESCE(
          CASE
            WHEN a.entity_type='lead' THEN l.company_name
            WHEN a.entity_type='customer' THEN s.shipper_name
            WHEN a.entity_type='opportunity' THEN o.account_name
            ELSE NULL
          END, ''
        ) AS company_name,
        CASE
          WHEN a.entity_type='lead' THEN l.service_type
          WHEN a.entity_type='opportunity' THEN CONCAT_WS(' • ',
            o.shipment_type,
            CONCAT(o.transport_mode, IFNULL(CONCAT('/', o.sea_type), '')),
            o.service_level
          )
          ELSE NULL
        END AS service,
        CASE
          WHEN a.entity_type='lead' THEN CONCAT_WS(' → ', l.origin_port, l.destination_port)
          WHEN a.entity_type='opportunity' THEN CONCAT_WS(' → ', o.origin, o.destination)
          ELSE NULL
        END AS route
    ", false);

    $qb->where('a.deleted_at IS NULL', null, false);
    $qb->where('a.created_at >=', $startDt);
    $qb->where('a.created_at <=', $endDt);

    if (!$this->isAll($region)) $qb->where('a.region_id', $region);
    if (!$this->isAll($sales))  $qb->where('a.owner_sales_id', (int)$sales);

    $activities = $qb->order_by('a.created_at', 'DESC')
                     ->limit(50)
                     ->get()
                     ->result_array();

    // ambil attachments per activity
    foreach ($activities as &$act) {
        $act['attachments'] = $this->dbx
            ->from('crm_activity_attachments')
            ->where('activity_id', $act['activity_id'])
            ->get()
            ->result_array();
    }

    return $activities;
}
}
