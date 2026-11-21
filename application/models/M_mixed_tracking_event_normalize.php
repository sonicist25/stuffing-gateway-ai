<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_mixed_tracking_event_normalize extends CI_Model
{
    public function normalize_latest_snapshots($shipper_id = 4407, $limit = 100)
    {
        // Kalau limit null / kosong / 0, artinya proses semua latest snapshot
        if ($limit === null || $limit === '' || (int)$limit <= 0) {
            $limit = null;
        } else {
            $limit = (int) $limit;
        }

        $rows = $this->get_latest_snapshot_rows($shipper_id, $limit);

        $summary = [
            'status' => true,
            'shipper_id' => $shipper_id,
            'limit' => $limit, // null berarti all
            'is_all' => ($limit === null),
            'total_snapshot' => count($rows),
            'total_insert_or_update_event' => 0,
            'success' => 0,
            'failed' => 0,
            'items' => []
        ];

        foreach ($rows as $row) {
            $res = $this->normalize_snapshot_row($row);

            if (!empty($res['status'])) {
                $summary['success']++;
                $summary['total_insert_or_update_event'] += (int) ($res['event_count'] ?? 0);
            } else {
                $summary['failed']++;
            }

            $summary['items'][] = $res;
        }

        return $summary;
    }

    public function normalize_one_latest_snapshot($sync_id)
    {
        $row = $this->get_one_latest_snapshot_row($sync_id);

        if (!$row) {
            return [
                'status' => false,
                'sync_id' => $sync_id,
                'message' => 'Latest snapshot not found'
            ];
        }

        return $this->normalize_snapshot_row($row);
    }

    private function get_latest_snapshot_rows($shipper_id, $limit = null)
    {
        $sql = "
            SELECT
                s.id AS sync_id,
                s.booking_id,
                s.shipper_id,
                s.customer_reference_no,
                s.tracking_number,
                s.hbl,
                s.pol,
                s.pot,
                s.pod,
                snap.id AS snapshot_id,
                snap.fetched_at,
                snap.response_body
            FROM tv_shipment_syncs s
            JOIN (
                SELECT 
                    sync_id,
                    MAX(id) AS latest_snapshot_id
                FROM tv_mixed_tracking_snapshots
                GROUP BY sync_id
            ) latest
                ON latest.sync_id = s.id
            JOIN tv_mixed_tracking_snapshots snap
                ON snap.id = latest.latest_snapshot_id
            WHERE s.shipper_id = ?
            AND s.hbl IS NOT NULL
            AND TRIM(s.hbl) <> ''
            ORDER BY snap.fetched_at DESC
        ";

        $params = [$shipper_id];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = (int) $limit;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    private function get_one_latest_snapshot_row($sync_id)
    {
        $sql = "
            SELECT
                s.id AS sync_id,
                s.booking_id,
                s.shipper_id,
                s.customer_reference_no,
                s.tracking_number,
                s.hbl,
                s.pol,
                s.pot,
                s.pod,
                snap.id AS snapshot_id,
                snap.fetched_at,
                snap.response_body
            FROM tv_shipment_syncs s
            JOIN tv_mixed_tracking_snapshots snap
                ON snap.sync_id = s.id
            WHERE s.id = ?
            ORDER BY snap.id DESC
            LIMIT 1
        ";

        return $this->db->query($sql, [(int) $sync_id])->row_array();
    }

    private function normalize_snapshot_row($row)
    {
        $sync_id     = (int) ($row['sync_id'] ?? 0);
        $snapshot_id = (int) ($row['snapshot_id'] ?? 0);
        $hbl         = trim((string) ($row['hbl'] ?? ''));

        $json = json_decode($row['response_body'], true);

        if (!is_array($json)) {
            return [
                'status' => false,
                'sync_id' => $sync_id,
                'snapshot_id' => $snapshot_id,
                'hbl' => $hbl,
                'message' => 'Invalid JSON response_body'
            ];
        }

        $eventList = [];

        if (!empty($json['event list']) && is_array($json['event list'])) {
            $eventList = $json['event list'];
        }

        if (empty($eventList)) {
            return [
                'status' => true,
                'sync_id' => $sync_id,
                'snapshot_id' => $snapshot_id,
                'hbl' => $hbl,
                'event_count' => 0,
                'message' => 'No event list found'
            ];
        }

        $inserted = 0;

        foreach ($eventList as $ev) {
            $eventName    = trim((string) ($ev['event name'] ?? ''));
            $portName     = trim((string) ($ev['port name'] ?? ''));
            $locationName = trim((string) ($ev['location name'] ?? ''));
            $eventTimeRaw = trim((string) ($ev['event time'] ?? ''));

            if ($eventName === '' || $eventTimeRaw === '') {
                continue;
            }

            $eventTime = $this->parse_mixed_event_datetime($eventTimeRaw);

            if ($eventTime === null) {
                continue;
            }

            $eventHashPayload = [
                'source'        => 'EVENT_LIST',
                'event_name'    => strtoupper($eventName),
                'port_name'     => strtoupper($portName),
                'location_name' => strtoupper($locationName),
                'event_time'    => $eventTime,
            ];

            $eventHash = hash('sha256', json_encode($eventHashPayload));

            $data = [
                'sync_id'               => $sync_id,
                'snapshot_id'           => $snapshot_id,
                'booking_id'            => $row['booking_id'] ?? null,
                'shipper_id'            => $row['shipper_id'] ?? null,
                'customer_reference_no' => $row['customer_reference_no'] ?? null,
                'tracking_number'       => $row['tracking_number'] ?? null,
                'hbl'                   => $hbl,
                'pol'                   => $row['pol'] ?? null,
                'pot'                   => $row['pot'] ?? null,
                'pod'                   => $row['pod'] ?? null,

                'event_source'          => 'EVENT_LIST',
                'event_no'              => isset($ev['no']) ? (int) $ev['no'] : null,
                'event_name'            => $eventName,
                'port_name'             => $portName,
                'location_name'         => $locationName,
                'vessel'                => $ev['vessel'] ?? null,
                'container_no'          => $ev['container'] ?? null,
                'event_time_raw'        => $eventTimeRaw,
                'event_time'            => $eventTime,
                'event_hash'            => $eventHash,
                'raw_event'             => json_encode($ev),

                'first_seen_at'         => date('Y-m-d H:i:s'),
                'last_seen_at'          => date('Y-m-d H:i:s'),
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ];

            $this->upsert_event($data);
            $inserted++;
        }

        return [
            'status' => true,
            'sync_id' => $sync_id,
            'snapshot_id' => $snapshot_id,
            'hbl' => $hbl,
            'event_count' => $inserted,
            'message' => 'OK'
        ];
    }

    private function upsert_event($data)
    {
        $sql = "
            INSERT INTO tv_mixed_tracking_events
            (
                sync_id,
                snapshot_id,
                booking_id,
                shipper_id,
                customer_reference_no,
                tracking_number,
                hbl,
                pol,
                pot,
                pod,
                event_source,
                event_no,
                event_name,
                port_name,
                location_name,
                vessel,
                container_no,
                event_time_raw,
                event_time,
                event_hash,
                raw_event,
                first_seen_at,
                last_seen_at,
                created_at,
                updated_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                snapshot_id = VALUES(snapshot_id),
                booking_id = VALUES(booking_id),
                shipper_id = VALUES(shipper_id),
                customer_reference_no = VALUES(customer_reference_no),
                tracking_number = VALUES(tracking_number),
                hbl = VALUES(hbl),
                pol = VALUES(pol),
                pot = VALUES(pot),
                pod = VALUES(pod),
                event_no = VALUES(event_no),
                event_name = VALUES(event_name),
                port_name = VALUES(port_name),
                location_name = VALUES(location_name),
                vessel = VALUES(vessel),
                container_no = VALUES(container_no),
                event_time_raw = VALUES(event_time_raw),
                event_time = VALUES(event_time),
                raw_event = VALUES(raw_event),
                last_seen_at = VALUES(last_seen_at),
                updated_at = VALUES(updated_at)
        ";

        $params = [
            $data['sync_id'],
            $data['snapshot_id'],
            $data['booking_id'],
            $data['shipper_id'],
            $data['customer_reference_no'],
            $data['tracking_number'],
            $data['hbl'],
            $data['pol'],
            $data['pot'],
            $data['pod'],
            $data['event_source'],
            $data['event_no'],
            $data['event_name'],
            $data['port_name'],
            $data['location_name'],
            $data['vessel'],
            $data['container_no'],
            $data['event_time_raw'],
            $data['event_time'],
            $data['event_hash'],
            $data['raw_event'],
            $data['first_seen_at'],
            $data['last_seen_at'],
            $data['created_at'],
            $data['updated_at'],
        ];

        return $this->db->query($sql, $params);
    }

    private function parse_mixed_event_datetime($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace([' WIB', ' WITA', ' WIT'], '', $value);
        $value = trim($value);

        // Khusus format slash: paksa sebagai d/m/Y, jangan kasih jatuh ke strtotime
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}:\d{2}(?::\d{2})?))?$/', $value, $m)) {
            $timePart = isset($m[4]) && $m[4] !== '' ? $m[4] : '00:00:00';

            if (substr_count($timePart, ':') === 1) {
                $timePart .= ':00';
            }

            $dateTimeString = $m[1] . '/' . $m[2] . '/' . $m[3] . ' ' . $timePart;

            $dt = DateTime::createFromFormat('d/m/Y H:i:s', $dateTimeString);
            $errors = DateTime::getLastErrors();

            if ($dt instanceof DateTime && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))) {
                return $dt->format('Y-m-d H:i:s');
            }

            return null;
        }

        // Format SQL / ISO
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:sP',
        ];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            $errors = DateTime::getLastErrors();

            if ($dt instanceof DateTime && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        // Fallback hanya untuk non-slash
        if (strpos($value, '/') === false) {
            $timestamp = strtotime($value);

            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }

        return null;
    }
}