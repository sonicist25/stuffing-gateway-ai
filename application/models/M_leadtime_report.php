<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model report leadtime Firmenich / Transvoyant mixed tracking.
 *
 * Final rules:
 * - Planned GI source: x_tracking_export.pickup_cargo JSON key "Pickup Cargo".
 * - Lane source: dynamic from x_tracking_export.destination.
 * - Lane format: Jakarta - {destination}. No fixed lane list.
 * - Top 10 lane: dynamic by COUNT(DISTINCT hbl_no) per period.
 * - Country is optional and stored as NULL.
 *
 * TT1 = Planned GI until actual vessel departure
 * TT2 = Actual vessel departure until actual POD arrival
 * - Final POD arrival uses local x_tracking_export.atd JSON key "Actual Time of Arrival (ATA)" first.
 * - Transhipment arrival/stepper arrival is not treated as POD arrival.
 * TT3 = Planned GI until actual POD arrival
 */
class M_leadtime_report extends CI_Model
{
    protected $default_sales_id = 33;

    public function __construct()
    {
        parent::__construct();
    }

    public function ensure_tables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS lt_monthly_report_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            shipper_id BIGINT UNSIGNED NOT NULL,
            period_month CHAR(7) NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            total_shipment INT NOT NULL DEFAULT 0,
            complete_shipment INT NOT NULL DEFAULT 0,
            missing_departure INT NOT NULL DEFAULT 0,
            missing_arrival INT NOT NULL DEFAULT 0,
            data_issue INT NOT NULL DEFAULT 0,
            complete_rate_percent DECIMAL(10,2) DEFAULT NULL,
            avg_tt1_days DECIMAL(10,2) DEFAULT NULL,
            avg_tt2_days DECIMAL(10,2) DEFAULT NULL,
            avg_tt3_days DECIMAL(10,2) DEFAULT NULL,
            generated_at DATETIME NOT NULL,
            generated_by VARCHAR(50) DEFAULT 'CRON',
            status ENUM('DRAFT','FINAL') NOT NULL DEFAULT 'DRAFT',
            note VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_shipper_period (shipper_id, period_month),
            KEY idx_period_month (period_month),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->db->query("CREATE TABLE IF NOT EXISTS lt_monthly_report_lane_summary (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            sort_no INT NOT NULL,
            lane VARCHAR(150) NOT NULL,
            country VARCHAR(100) DEFAULT NULL,
            total_shipment INT NOT NULL DEFAULT 0,
            complete_shipment INT NOT NULL DEFAULT 0,
            missing_departure INT NOT NULL DEFAULT 0,
            missing_arrival INT NOT NULL DEFAULT 0,
            data_issue INT NOT NULL DEFAULT 0,
            complete_rate_percent DECIMAL(10,2) DEFAULT NULL,
            avg_tt1_days DECIMAL(10,2) DEFAULT NULL,
            avg_tt2_days DECIMAL(10,2) DEFAULT NULL,
            avg_tt3_days DECIMAL(10,2) DEFAULT NULL,
            best_tt3_days DECIMAL(10,2) DEFAULT NULL,
            worst_tt3_days DECIMAL(10,2) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_run_id (run_id),
            KEY idx_lane (lane),
            KEY idx_sort_no (sort_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->db->query("CREATE TABLE IF NOT EXISTS lt_monthly_report_details (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            lane VARCHAR(150) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            hbl_no VARCHAR(100) NOT NULL,
            no_job VARCHAR(100) DEFAULT NULL,
            destination VARCHAR(150) DEFAULT NULL,
            consol_destination VARCHAR(150) DEFAULT NULL,
            planned_gi_at DATETIME DEFAULT NULL,
            actual_departure_at DATETIME DEFAULT NULL,
            actual_pod_arrival_at DATETIME DEFAULT NULL,
            tt1_days DECIMAL(10,2) DEFAULT NULL,
            tt2_days DECIMAL(10,2) DEFAULT NULL,
            tt3_days DECIMAL(10,2) DEFAULT NULL,
            lt_status VARCHAR(50) DEFAULT NULL,
            lt_issue_note VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_run_id (run_id),
            KEY idx_hbl_no (hbl_no),
            KEY idx_lane (lane),
            KEY idx_status (lt_status),
            KEY idx_planned_gi (planned_gi_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        return true;
    }

    /**
     * Recalculate live leadtime into x_tracking_export.
     * Tidak memakai x_tracking_export.id karena table server tidak punya kolom id.
     */
    public function recalculate_leadtime($shipper_id = 4407, $date_from = '2026-05-01', $date_to = null, $sales_id = null)
    {
        $shipper_id = (int) $shipper_id;
        $sales_id   = $this->normalize_sales_id($sales_id);
        $date_from  = $this->normalize_date($date_from, '2026-05-01');
        $date_to    = $this->normalize_date($date_to, date('Y-m-d', strtotime('+1 day')));

        $sql = <<<'SQL'
UPDATE x_tracking_export x

LEFT JOIN (
    SELECT
        p.hbl_key,
        CASE
            WHEN p.pickup_clean IS NULL
              OR p.pickup_clean = ''
              OR LOWER(p.pickup_clean) = 'null'
                THEN NULL
            WHEN p.pickup_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(p.pickup_clean, '%d/%m/%Y %H:%i:%s')
            WHEN p.pickup_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(p.pickup_clean, '%d/%m/%Y %H:%i')
            WHEN p.pickup_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(p.pickup_clean, '%Y-%m-%d %H:%i:%s')
            WHEN p.pickup_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(p.pickup_clean, '%Y-%m-%d %H:%i')
            ELSE NULL
        END AS planned_gi_at
    FROM (
        SELECT
            UPPER(TRIM(hbl_no)) AS hbl_key,
            TRIM(
                SUBSTRING_INDEX(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    CASE
                                        WHEN pickup_cargo IS NULL
                                          OR TRIM(pickup_cargo) = ''
                                          OR LOWER(TRIM(pickup_cargo)) = 'null'
                                            THEN NULL
                                        WHEN JSON_VALID(pickup_cargo) = 1
                                            THEN JSON_UNQUOTE(JSON_EXTRACT(pickup_cargo, '$."Pickup Cargo"'))
                                        ELSE pickup_cargo
                                    END,
                                    '*', ''
                                ),
                                '\r', ''
                            ),
                            '\n', ''
                        ),
                        '"', ''
                    ),
                    '(',
                    1
                )
            ) AS pickup_clean
        FROM x_tracking_export
        WHERE id_shipper = ?
          AND id_sales = ?
    ) p
) plan
    ON plan.hbl_key = UPPER(TRIM(x.hbl_no))

LEFT JOIN (
    SELECT
        parsed.hbl_key,
        CASE WHEN parsed.local_departure_at > '1970-01-01' AND parsed.local_departure_at <= NOW()
             THEN parsed.local_departure_at ELSE NULL END AS local_departure_at,
        CASE WHEN parsed.local_pod_arrival_at > '1970-01-01'
              AND parsed.local_pod_arrival_at <= NOW()
              
             THEN parsed.local_pod_arrival_at ELSE NULL END AS local_pod_arrival_at
    FROM (
    SELECT
        la.hbl_key,
        la.eta_source,
        la.known_eta,
        CASE
            WHEN la.local_departure_clean IS NULL OR la.local_departure_clean = '' OR LOWER(la.local_departure_clean) = 'null'
                THEN NULL
            WHEN la.local_departure_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(la.local_departure_clean, '%d/%m/%Y %H:%i:%s')
            WHEN la.local_departure_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
                THEN STR_TO_DATE(CONCAT(la.local_departure_clean, ' 00:00:00'), '%d/%m/%Y %H:%i:%s')
            WHEN la.local_departure_clean REGEXP '^[0-9]{4}[0-9]{2}[0-9]{2}$'
                THEN STR_TO_DATE(la.local_departure_clean, '%Y%m%d')
            WHEN la.local_departure_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(la.local_departure_clean, '%Y-%m-%d %H:%i:%s')
            WHEN la.local_departure_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                THEN STR_TO_DATE(CONCAT(la.local_departure_clean, ' 00:00:00'), '%Y-%m-%d %H:%i:%s')
            ELSE NULL
        END AS local_departure_at,
        CASE
            WHEN la.local_arrival_clean IS NULL OR la.local_arrival_clean = '' OR LOWER(la.local_arrival_clean) = 'null'
                THEN NULL
            WHEN la.local_arrival_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(la.local_arrival_clean, '%d/%m/%Y %H:%i:%s')
            WHEN la.local_arrival_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
                THEN STR_TO_DATE(CONCAT(la.local_arrival_clean, ' 00:00:00'), '%d/%m/%Y %H:%i:%s')
            WHEN la.local_arrival_clean REGEXP '^[0-9]{4}[0-9]{2}[0-9]{2}$'
                THEN STR_TO_DATE(la.local_arrival_clean, '%Y%m%d')
            WHEN la.local_arrival_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(la.local_arrival_clean, '%Y-%m-%d %H:%i:%s')
            WHEN la.local_arrival_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                THEN STR_TO_DATE(CONCAT(la.local_arrival_clean, ' 00:00:00'), '%Y-%m-%d %H:%i:%s')
            ELSE NULL
        END AS local_pod_arrival_at
    FROM (
        SELECT
            UPPER(TRIM(hbl_no)) AS hbl_key,
            lt_pod_eta_source AS eta_source,
            lt_pod_eta_at AS known_eta,
            TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(
                CASE
                    WHEN dpol IS NULL OR TRIM(dpol) = '' OR LOWER(TRIM(dpol)) = 'null' THEN NULL
                    WHEN JSON_VALID(dpol) = 1 THEN JSON_UNQUOTE(JSON_EXTRACT(dpol, '$."Actual Time of Departure (ATD)"'))
                    ELSE dpol
                END,
                '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS local_departure_clean,
            TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(
                CASE
                    WHEN atd IS NULL OR TRIM(atd) = '' OR LOWER(TRIM(atd)) = 'null' THEN NULL
                    WHEN JSON_VALID(atd) = 1 THEN JSON_UNQUOTE(JSON_EXTRACT(atd, '$."Actual Time of Arrival (ATA)"'))
                    ELSE atd
                END,
                '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS local_arrival_clean
        FROM x_tracking_export
        WHERE id_shipper = ?
          AND id_sales = ?
    ) la
    ) parsed
) local_actual
    ON local_actual.hbl_key = UPPER(TRIM(x.hbl_no))

LEFT JOIN (
    SELECT
        s.hbl,
        COALESCE(
            CASE WHEN ev.event_departure_at > '1970-01-01' AND ev.event_departure_at <= NOW() THEN ev.event_departure_at END,
            CASE WHEN raw.routing_departure_at > '1970-01-01' AND raw.routing_departure_at <= NOW() THEN raw.routing_departure_at END,
            CASE WHEN raw.stepper_departure_at > '1970-01-01' AND raw.stepper_departure_at <= NOW() THEN raw.stepper_departure_at END
        ) AS actual_departure_at,
        CASE
            WHEN ev.event_arrival_at > '1970-01-01' AND ev.event_arrival_at <= NOW()
                THEN ev.event_arrival_at
            WHEN raw.routing_discharge_at > '1970-01-01'
             AND raw.routing_discharge_at <= NOW()
             AND UPPER(IFNULL(raw.routing_discharge_raw, '')) NOT LIKE '%PREDICTED%'
             AND (
                    UPPER(IFNULL(raw.api_status, '')) IN ('COMPLETED','COMPLETE','DELIVERED')
                 OR UPPER(IFNULL(s.sync_status, '')) IN ('DELIVERED','COMPLETED','STOPPED')
                 )
                THEN raw.routing_discharge_at
            ELSE NULL
        END AS actual_pod_arrival_at
    FROM tv_shipment_syncs s

    LEFT JOIN (
        SELECT
            rc.sync_id,
            rc.api_status,
            rc.routing_discharge_raw,
            rc.stepper_arrival_raw,
            CASE
                WHEN rc.routing_departure_clean IS NULL OR rc.routing_departure_clean = '' OR LOWER(rc.routing_departure_clean) = 'null'
                    THEN NULL
                WHEN rc.routing_departure_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_departure_clean, '%d/%m/%Y %H:%i:%s')
                WHEN rc.routing_departure_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_departure_clean, '%d/%m/%Y %H:%i')
                WHEN rc.routing_departure_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_departure_clean, '%Y-%m-%d %H:%i:%s')
                WHEN rc.routing_departure_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_departure_clean, '%Y-%m-%d %H:%i')
                ELSE NULL
            END AS routing_departure_at,
            CASE
                WHEN rc.routing_discharge_clean IS NULL OR rc.routing_discharge_clean = '' OR LOWER(rc.routing_discharge_clean) = 'null'
                    THEN NULL
                WHEN rc.routing_discharge_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_discharge_clean, '%d/%m/%Y %H:%i:%s')
                WHEN rc.routing_discharge_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_discharge_clean, '%d/%m/%Y %H:%i')
                WHEN rc.routing_discharge_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_discharge_clean, '%Y-%m-%d %H:%i:%s')
                WHEN rc.routing_discharge_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$'
                    THEN STR_TO_DATE(rc.routing_discharge_clean, '%Y-%m-%d %H:%i')
                ELSE NULL
            END AS routing_discharge_at,
            CASE
                WHEN (
                        UPPER(IFNULL(rc.stepper0_type, '')) LIKE '%ORIGIN DEPARTURE%'
                     OR UPPER(IFNULL(rc.stepper0_type, '')) LIKE '%DEPARTURE%'
                     )
                 AND rc.stepper_departure_clean IS NOT NULL
                 AND rc.stepper_departure_clean <> ''
                 AND LOWER(rc.stepper_departure_clean) <> 'null'
                 AND rc.stepper_departure_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}(:[0-9]{2})?$'
                    THEN IF(
                        rc.stepper_departure_clean REGEXP ':[0-9]{2}$',
                        STR_TO_DATE(rc.stepper_departure_clean, '%d/%m/%Y %H:%i:%s'),
                        STR_TO_DATE(rc.stepper_departure_clean, '%d/%m/%Y %H:%i')
                    )
                ELSE NULL
            END AS stepper_departure_at,
            CASE
                WHEN UPPER(IFNULL(rc.stepper1_type, '')) LIKE '%ARRIVAL%'
                 AND rc.stepper_arrival_clean IS NOT NULL
                 AND rc.stepper_arrival_clean <> ''
                 AND LOWER(rc.stepper_arrival_clean) <> 'null'
                 AND rc.stepper_arrival_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}(:[0-9]{2})?$'
                    THEN IF(
                        rc.stepper_arrival_clean REGEXP ':[0-9]{2}$',
                        STR_TO_DATE(rc.stepper_arrival_clean, '%d/%m/%Y %H:%i:%s'),
                        STR_TO_DATE(rc.stepper_arrival_clean, '%d/%m/%Y %H:%i')
                    )
                ELSE NULL
            END AS stepper_arrival_at
        FROM (
            SELECT
                snap.sync_id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.header[0][0].status')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.header[0].status'))
                ) AS api_status,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[3]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[2]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[1]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][3]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][2]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][1]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][0]."time of discharge"'))
                ) AS routing_discharge_raw,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1]."event time"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1][0]."event time"'))
                ) AS stepper_arrival_raw,
                TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0]."time of departure"')), JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][0]."time of departure"')), ''), '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS routing_departure_clean,
                TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[3]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[2]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[1]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][3]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][2]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][1]."time of discharge"')),
                    JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.routing[0][0]."time of discharge"')),
                    ''), '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS routing_discharge_clean,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[0]."location type"')), JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[0][0]."location type"'))) AS stepper0_type,
                TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[0]."event time"')), JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[0][0]."event time"')), ''), '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS stepper_departure_clean,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1]."location type"')), JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1][0]."location type"'))) AS stepper1_type,
                TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1]."event time"')), JSON_UNQUOTE(JSON_EXTRACT(snap.response_body, '$.stepper[1][0]."event time"')), ''), '*', ''), '\r', ''), '\n', ''), '"', ''), '(', 1)) AS stepper_arrival_clean
            FROM tv_mixed_tracking_snapshots snap
            JOIN (
                SELECT sync_id, MAX(fetched_at) AS latest_fetched_at
                FROM tv_mixed_tracking_snapshots
                GROUP BY sync_id
            ) latest
                ON latest.sync_id = snap.sync_id
               AND latest.latest_fetched_at = snap.fetched_at
        ) rc
    ) raw
        ON raw.sync_id = s.id

    LEFT JOIN (
        SELECT
            hbl,
            COALESCE(
                MIN(CASE WHEN UPPER(event_name) LIKE 'SHIPMENT DEPARTED FROM ORIGIN PORT%' THEN event_time END),
                MIN(CASE WHEN UPPER(event_name) LIKE '%DEPARTED FROM ORIGIN PORT%'
                       OR UPPER(event_name) = 'DEPARTURE FROM ORIGIN PORT'
                       OR UPPER(location_name) LIKE '%ORIGIN DEPARTURE%'
                       OR UPPER(location_name) LIKE '%DEPARTURE%'
                    THEN event_time END)
            ) AS event_departure_at,
            MIN(CASE WHEN (
                        UPPER(event_name) LIKE '%ARRIVED AT DESTINATION PORT%'
                     OR UPPER(event_name) = 'ARRIVAL AT FINAL PORT OF DESTINATION'
                     OR UPPER(event_name) LIKE '%FINAL PORT OF DESTINATION%'
                     OR UPPER(event_name) LIKE '%DESTINATION PORT%'
                    )
                    AND event_time <= NOW()
                THEN event_time END) AS event_arrival_at
        FROM tv_mixed_tracking_events
        WHERE shipper_id = ?
          AND event_time IS NOT NULL
        GROUP BY hbl
    ) ev
        ON UPPER(TRIM(ev.hbl)) = UPPER(TRIM(s.hbl))

    WHERE s.shipper_id = ?
) det
    ON UPPER(TRIM(det.hbl)) = UPPER(TRIM(x.hbl_no))

SET
    x.lt_planned_gi_at = plan.planned_gi_at,
    x.lt_actual_departure_at = COALESCE(local_actual.local_departure_at, det.actual_departure_at),
    x.lt_actual_pod_arrival_at = __V6_ARRIVAL__,
    x.lt_tt1_days = CASE
        WHEN plan.planned_gi_at IS NOT NULL AND COALESCE(local_actual.local_departure_at, det.actual_departure_at) IS NOT NULL
            THEN ROUND(TIMESTAMPDIFF(HOUR, plan.planned_gi_at, COALESCE(local_actual.local_departure_at, det.actual_departure_at)) / 24, 2)
        ELSE NULL
    END,
    x.lt_tt2_days = CASE
        WHEN COALESCE(local_actual.local_departure_at, det.actual_departure_at) IS NOT NULL AND __V6_ARRIVAL__ IS NOT NULL
            THEN ROUND(TIMESTAMPDIFF(HOUR, COALESCE(local_actual.local_departure_at, det.actual_departure_at), __V6_ARRIVAL__) / 24, 2)
        ELSE NULL
    END,
    x.lt_tt3_days = CASE
        WHEN plan.planned_gi_at IS NOT NULL AND __V6_ARRIVAL__ IS NOT NULL
            THEN ROUND(TIMESTAMPDIFF(HOUR, plan.planned_gi_at, __V6_ARRIVAL__) / 24, 2)
        ELSE NULL
    END,
    x.lt_lane_name = CONCAT('Jakarta - ', TRIM(COALESCE(NULLIF(x.destination, ''), NULLIF(x.consol_destination, ''), 'UNKNOWN'))),
    x.lt_destination_country = NULL,
    x.lt_status = CASE
        WHEN plan.planned_gi_at IS NULL
            THEN 'MISSING_PLANNED_GI'
        WHEN COALESCE(local_actual.local_departure_at, det.actual_departure_at) IS NULL
            THEN 'MISSING_DEPARTURE'
        WHEN __V6_ARRIVAL__ IS NULL
            THEN 'MISSING_ARRIVAL'
        WHEN __V6_ARRIVAL__ < COALESCE(local_actual.local_departure_at, det.actual_departure_at)
            THEN 'DATA_ISSUE'
        WHEN COALESCE(local_actual.local_departure_at, det.actual_departure_at) < plan.planned_gi_at
            THEN 'DATA_ISSUE'
        ELSE 'COMPLETE'
    END,
    x.lt_issue_note = CASE
        WHEN plan.planned_gi_at IS NULL
            THEN 'Pickup Cargo kosong / invalid'
        WHEN COALESCE(local_actual.local_departure_at, det.actual_departure_at) IS NULL
            THEN 'Actual vessel departure belum ditemukan dari mixed event/routing'
        WHEN __V6_ARRIVAL__ IS NULL
            THEN 'Actual POD arrival belum ditemukan / arrival masih future'
        WHEN __V6_ARRIVAL__ < COALESCE(local_actual.local_departure_at, det.actual_departure_at)
            THEN 'Arrival lebih awal dari departure'
        WHEN COALESCE(local_actual.local_departure_at, det.actual_departure_at) < plan.planned_gi_at
            THEN 'Departure lebih awal dari Pickup Cargo'
        WHEN __V6_PROXY_NOTE__ THEN 'POD arrival uses agent ETA proxy (not confirmed actual)'
        ELSE NULL
    END,
    x.lt_calculated_at = NOW()

WHERE x.id_shipper = ?
  AND x.id_sales = ?
  AND x.hbl_no IS NOT NULL
  AND TRIM(x.hbl_no) <> ''
  AND plan.planned_gi_at >= ?
  AND plan.planned_gi_at < ?
SQL;

        // Use identical resolved arrival for the stored date, TT2/TT3 and status.
        // Structured ETA is eligible only for known proxy agents and the correct leg.
        $port = static function ($field) {
            $v = "UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE($field,'')), ' ', ''), '-', ''), '_', ''))";
            return "(CASE WHEN $v IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' ELSE $v END)";
        };
        $dest = $port('x.destination');
        $hub = $port('x.consol_destination');
        $connecting = $port('x.connecting');
        $direct = "($dest = 'SINGAPORE' AND $hub = 'SINGAPORE')";
        $transit = "($hub = 'SINGAPORE' AND $dest NOT IN ('','NULL','N/A','NA','UNKNOWN','0','NONE','TBA','TBC','SINGAPORE') AND $connecting <> $dest)";
        $benkel = "UPPER(TRIM(x.id_agent)) IN ('BENKEL','BENKEL INT','BKL')";
        $astro = "UPPER(TRIM(x.id_agent)) IN ('ASTSIN','ASTRO','AST','ASTRO PACI')";
        $eligible = "(($benkel AND (($direct AND x.lt_pod_eta_source = 'BENKEL_IMPORT_ETA') OR ($transit AND x.lt_pod_eta_source = 'BENKEL_EXPORT_ETA') OR (($direct OR $transit) AND x.lt_pod_eta_source = 'BACKFILL_BENKEL_ETA'))) OR ($astro AND (($direct AND x.lt_pod_eta_source = 'ASTRO_ETA_SIN') OR ($transit AND x.lt_pod_eta_source = 'ASTRO_2ND_ETA'))))";
        $ata = "(CASE WHEN x.ata REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$' THEN STR_TO_DATE(x.ata,'%d/%m/%Y') WHEN x.ata REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN STR_TO_DATE(x.ata,'%Y-%m-%d') WHEN x.ata REGEXP '^[0-9]{8}$' THEN STR_TO_DATE(x.ata,'%Y%m%d') ELSE NULL END)";
        $local = 'local_actual.local_pod_arrival_at';
        $json = "IF(JSON_VALID(x.atd), x.atd, '{}')";
        $marker = "JSON_UNQUOTE(JSON_EXTRACT($json, '$.\"Arrival Source\"'))";
        $markerDate = "JSON_UNQUOTE(JSON_EXTRACT($json, '$.\"Proxy Arrival Date\"'))";
        $marked = "(COALESCE($marker,'') = 'AGENT_ETA_PROXY' AND COALESCE($markerDate,'') = DATE_FORMAT($local,'%Y-%m-%d'))";
        $arrival = "(CASE WHEN $eligible THEN CASE
            WHEN $direct AND $ata > '1970-01-01' AND $ata <= NOW() THEN $ata
            WHEN $local IS NOT NULL AND (COALESCE(x.ata_by,'') <> '' OR (NOT $marked AND (x.lt_pod_eta_at IS NULL OR DATE($local) <> DATE(x.lt_pod_eta_at)))) THEN $local
            WHEN x.lt_pod_eta_at > '1970-01-01' AND x.lt_pod_eta_at <= NOW() THEN DATE(x.lt_pod_eta_at)
            ELSE NULL END
            ELSE COALESCE($local, det.actual_pod_arrival_at) END)";
        $proxyNote = "($eligible AND x.lt_pod_eta_at > '1970-01-01' AND x.lt_pod_eta_at <= NOW() AND NOT ($direct AND COALESCE($ata > '1970-01-01' AND $ata <= NOW(),0)) AND COALESCE(x.ata_by,'') = '' AND ($local IS NULL OR $marked OR DATE($local) = DATE(x.lt_pod_eta_at)))";
        $sql = str_replace(['__V6_ARRIVAL__','__V6_PROXY_NOTE__'], [$arrival,$proxyNote], $sql);

        $this->db->query($sql, [
            (string) $shipper_id, // plan.id_shipper
            (string) $sales_id,   // plan.id_sales
            (string) $shipper_id, // local_actual.id_shipper
            (string) $sales_id,   // local_actual.id_sales
            (string) $shipper_id, // events.shipper_id
            (string) $shipper_id, // tv_shipment_syncs.shipper_id
            (string) $shipper_id, // x.id_shipper
            (string) $sales_id,   // x.id_sales
            $date_from,
            $date_to,
        ]);

        return $this->db->affected_rows();
    }

    public function generate_monthly_report($shipper_id = 4407, $period = null, $status = 'DRAFT', $sales_id = null)
    {
        $this->ensure_tables();

        $shipper_id = (int) $shipper_id;
        $sales_id   = $this->normalize_sales_id($sales_id);
        $period     = $this->normalize_period($period);
        $status     = strtoupper((string) $status) === 'FINAL' ? 'FINAL' : 'DRAFT';

        $date_from = $period . '-01';
        $date_to   = date('Y-m-d', strtotime($date_from . ' +1 month'));
        $now       = date('Y-m-d H:i:s');

        $this->db->trans_begin();

        $affected = $this->recalculate_leadtime($shipper_id, $date_from, $date_to, $sales_id);

        $oldRun = $this->db
            ->where('shipper_id', $shipper_id)
            ->where('period_month', $period)
            ->get('lt_monthly_report_runs')
            ->row_array();

        if ($oldRun) {
            $oldRunId = (int) $oldRun['id'];
            $this->db->where('run_id', $oldRunId)->delete('lt_monthly_report_details');
            $this->db->where('run_id', $oldRunId)->delete('lt_monthly_report_lane_summary');
            $this->db->where('id', $oldRunId)->delete('lt_monthly_report_runs');
        }

        $this->db->insert('lt_monthly_report_runs', [
            'shipper_id'    => $shipper_id,
            'period_month'  => $period,
            'date_from'     => $date_from,
            'date_to'       => $date_to,
            'generated_at'  => $now,
            'generated_by'  => 'CRON',
            'status'        => $status,
            'note'          => 'Generated from dynamic destination top 10 and pickup cargo planned GI',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $run_id = (int) $this->db->insert_id();

        $this->insert_monthly_lane_summary($run_id, $shipper_id, $date_from, $date_to, $sales_id);
        $this->insert_monthly_detail($run_id, $shipper_id, $date_from, $date_to, $sales_id);
        $this->update_monthly_run_totals($run_id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            throw new Exception('Generate monthly leadtime report failed');
        }

        $this->db->trans_commit();

        return [
            'run_id'        => $run_id,
            'shipper_id'    => $shipper_id,
            'period'        => $period,
            'date_from'     => $date_from,
            'date_to'       => $date_to,
            'status'        => $status,
            'affected_rows' => $affected,
            'sales_id'      => $sales_id,
        ];
    }

    public function close_month($shipper_id = 4407, $period = null, $sales_id = null)
    {
        return $this->generate_monthly_report($shipper_id, $period, 'DRAFT', $sales_id);
    }

    public function finalize_report($run_id)
    {
        $this->db->where('id', (int) $run_id)->update('lt_monthly_report_runs', [
            'status'     => 'FINAL',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() >= 0;
    }

    private function insert_monthly_lane_summary($run_id, $shipper_id, $date_from, $date_to, $sales_id)
    {
        $sql = <<<'SQL'
INSERT INTO lt_monthly_report_lane_summary (
    run_id,
    sort_no,
    lane,
    country,
    total_shipment,
    complete_shipment,
    missing_departure,
    missing_arrival,
    data_issue,
    complete_rate_percent,
    avg_tt1_days,
    avg_tt2_days,
    avg_tt3_days,
    best_tt3_days,
    worst_tt3_days,
    created_at
)
SELECT
    ? AS run_id,
    top_lanes.sort_no,
    top_lanes.lane,
    NULL AS country,
    COUNT(DISTINCT x.hbl_no) AS total_shipment,
    COUNT(DISTINCT CASE WHEN x.lt_status = 'COMPLETE' THEN x.hbl_no END) AS complete_shipment,
    COUNT(DISTINCT CASE WHEN x.lt_status = 'MISSING_DEPARTURE' THEN x.hbl_no END) AS missing_departure,
    COUNT(DISTINCT CASE WHEN x.lt_status = 'MISSING_ARRIVAL' THEN x.hbl_no END) AS missing_arrival,
    COUNT(DISTINCT CASE WHEN x.lt_status = 'DATA_ISSUE' THEN x.hbl_no END) AS data_issue,
    ROUND(100 * COUNT(DISTINCT CASE WHEN x.lt_status = 'COMPLETE' THEN x.hbl_no END) / NULLIF(COUNT(DISTINCT x.hbl_no), 0), 2) AS complete_rate_percent,
    ROUND(AVG(CASE WHEN x.lt_status = 'COMPLETE' THEN x.lt_tt1_days END), 2) AS avg_tt1_days,
    ROUND(AVG(CASE WHEN x.lt_status = 'COMPLETE' THEN x.lt_tt2_days END), 2) AS avg_tt2_days,
    ROUND(AVG(CASE WHEN x.lt_status = 'COMPLETE' THEN x.lt_tt3_days END), 2) AS avg_tt3_days,
    ROUND(MIN(CASE WHEN x.lt_status = 'COMPLETE' THEN x.lt_tt3_days END), 2) AS best_tt3_days,
    ROUND(MAX(CASE WHEN x.lt_status = 'COMPLETE' THEN x.lt_tt3_days END), 2) AS worst_tt3_days,
    NOW() AS created_at
FROM (
    SELECT
        @rn := @rn + 1 AS sort_no,
        ranked.lane
    FROM (
        SELECT
            lt_lane_name AS lane,
            COUNT(DISTINCT hbl_no) AS total_shipment
        FROM x_tracking_export
        WHERE id_shipper = ?
          AND id_sales = ?
          AND lt_planned_gi_at >= ?
          AND lt_planned_gi_at < ?
          AND lt_lane_name IS NOT NULL
          AND TRIM(lt_lane_name) <> ''
        GROUP BY lt_lane_name
        ORDER BY total_shipment DESC, lt_lane_name ASC
        LIMIT 10
    ) ranked
    CROSS JOIN (SELECT @rn := 0) vars
) top_lanes
JOIN x_tracking_export x
    ON x.lt_lane_name = top_lanes.lane
   AND x.id_shipper = ?
   AND x.id_sales = ?
   AND x.lt_planned_gi_at >= ?
   AND x.lt_planned_gi_at < ?
GROUP BY
    top_lanes.sort_no,
    top_lanes.lane
ORDER BY top_lanes.sort_no
SQL;

        $this->db->query($sql, [
            (int) $run_id,
            (string) $shipper_id,
            (string) $sales_id,
            $date_from,
            $date_to,
            (string) $shipper_id,
            (string) $sales_id,
            $date_from,
            $date_to,
        ]);
    }

    private function insert_monthly_detail($run_id, $shipper_id, $date_from, $date_to, $sales_id)
    {
        $sql = <<<'SQL'
INSERT INTO lt_monthly_report_details (
    run_id,
    lane,
    country,
    hbl_no,
    no_job,
    destination,
    consol_destination,
    planned_gi_at,
    actual_departure_at,
    actual_pod_arrival_at,
    tt1_days,
    tt2_days,
    tt3_days,
    lt_status,
    lt_issue_note,
    created_at
)
SELECT
    ? AS run_id,
    x.lt_lane_name AS lane,
    NULL AS country,
    x.hbl_no,
    x.no_job,
    x.destination,
    x.consol_destination,
    x.lt_planned_gi_at,
    x.lt_actual_departure_at,
    x.lt_actual_pod_arrival_at,
    x.lt_tt1_days,
    x.lt_tt2_days,
    x.lt_tt3_days,
    x.lt_status,
    x.lt_issue_note,
    NOW()
FROM x_tracking_export x
JOIN (
    SELECT
        lt_lane_name AS lane,
        COUNT(DISTINCT hbl_no) AS total_shipment
    FROM x_tracking_export
    WHERE id_shipper = ?
      AND id_sales = ?
      AND lt_planned_gi_at >= ?
      AND lt_planned_gi_at < ?
      AND lt_lane_name IS NOT NULL
      AND TRIM(lt_lane_name) <> ''
    GROUP BY lt_lane_name
    ORDER BY total_shipment DESC, lt_lane_name ASC
    LIMIT 10
) top_lanes
    ON top_lanes.lane = x.lt_lane_name
WHERE x.id_shipper = ?
  AND x.id_sales = ?
  AND x.lt_planned_gi_at >= ?
  AND x.lt_planned_gi_at < ?
ORDER BY
    top_lanes.total_shipment DESC,
    x.lt_lane_name ASC,
    x.lt_planned_gi_at ASC,
    x.hbl_no ASC
SQL;

        $this->db->query($sql, [
            (int) $run_id,
            (string) $shipper_id,
            (string) $sales_id,
            $date_from,
            $date_to,
            (string) $shipper_id,
            (string) $sales_id,
            $date_from,
            $date_to,
        ]);
    }

    private function update_monthly_run_totals($run_id)
    {
        $sql = <<<'SQL'
UPDATE lt_monthly_report_runs r
JOIN (
    SELECT
        run_id,
        COUNT(DISTINCT hbl_no) AS total_shipment,
        COUNT(DISTINCT CASE WHEN lt_status = 'COMPLETE' THEN hbl_no END) AS complete_shipment,
        COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_DEPARTURE' THEN hbl_no END) AS missing_departure,
        COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_ARRIVAL' THEN hbl_no END) AS missing_arrival,
        COUNT(DISTINCT CASE WHEN lt_status = 'DATA_ISSUE' THEN hbl_no END) AS data_issue,
        ROUND(100 * COUNT(DISTINCT CASE WHEN lt_status = 'COMPLETE' THEN hbl_no END) / NULLIF(COUNT(DISTINCT hbl_no), 0), 2) AS complete_rate_percent,
        ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN tt1_days END), 2) AS avg_tt1_days,
        ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN tt2_days END), 2) AS avg_tt2_days,
        ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN tt3_days END), 2) AS avg_tt3_days
    FROM lt_monthly_report_details
    WHERE run_id = ?
    GROUP BY run_id
) d
    ON d.run_id = r.id
SET
    r.total_shipment = d.total_shipment,
    r.complete_shipment = d.complete_shipment,
    r.missing_departure = d.missing_departure,
    r.missing_arrival = d.missing_arrival,
    r.data_issue = d.data_issue,
    r.complete_rate_percent = d.complete_rate_percent,
    r.avg_tt1_days = d.avg_tt1_days,
    r.avg_tt2_days = d.avg_tt2_days,
    r.avg_tt3_days = d.avg_tt3_days,
    r.updated_at = NOW()
WHERE r.id = ?
SQL;

        $this->db->query($sql, [(int) $run_id, (int) $run_id]);
    }

    public function get_live_kpi($shipper_id = 4407, $date_from = null, $date_to = null, $sales_id = null)
    {
        $shipper_id = (int) $shipper_id;
        $sales_id   = $this->normalize_sales_id($sales_id);
        $date_from  = $this->normalize_date($date_from, date('Y-m-01'));
        $date_to    = $this->normalize_date($date_to, date('Y-m-d', strtotime('+1 day')));

        $sql = <<<'SQL'
SELECT
    COUNT(DISTINCT hbl_no) AS total_shipment,
    COUNT(DISTINCT CASE WHEN lt_status = 'COMPLETE' THEN hbl_no END) AS complete_shipment,
    COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_DEPARTURE' THEN hbl_no END) AS missing_departure,
    COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_ARRIVAL' THEN hbl_no END) AS missing_arrival,
    COUNT(DISTINCT CASE WHEN lt_status = 'DATA_ISSUE' THEN hbl_no END) AS data_issue,
    ROUND(100 * COUNT(DISTINCT CASE WHEN lt_status = 'COMPLETE' THEN hbl_no END) / NULLIF(COUNT(DISTINCT hbl_no), 0), 2) AS complete_rate_percent,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt1_days END), 2) AS avg_tt1_days,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt2_days END), 2) AS avg_tt2_days,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt3_days END), 2) AS avg_tt3_days
FROM x_tracking_export
WHERE id_shipper = ?
  AND id_sales = ?
  AND lt_planned_gi_at >= ?
  AND lt_planned_gi_at < ?
  AND lt_lane_name IS NOT NULL
  AND TRIM(lt_lane_name) <> ''
SQL;

        return $this->db->query($sql, [(string) $shipper_id, (string) $sales_id, $date_from, $date_to])->row_array();
    }

    public function get_live_top_lanes($shipper_id = 4407, $date_from = null, $date_to = null, $limit = 10, $sales_id = null)
    {
        $shipper_id = (int) $shipper_id;
        $sales_id   = $this->normalize_sales_id($sales_id);
        $date_from  = $this->normalize_date($date_from, date('Y-m-01'));
        $date_to    = $this->normalize_date($date_to, date('Y-m-d', strtotime('+1 day')));
        $limit      = max(1, min(50, (int) $limit));

        $sql = "
SELECT
    lt_lane_name AS lane,
    NULL AS country,
    COUNT(DISTINCT hbl_no) AS total_shipment,
    COUNT(DISTINCT CASE WHEN lt_status = 'COMPLETE' THEN hbl_no END) AS complete_shipment,
    COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_DEPARTURE' THEN hbl_no END) AS missing_departure,
    COUNT(DISTINCT CASE WHEN lt_status = 'MISSING_ARRIVAL' THEN hbl_no END) AS missing_arrival,
    COUNT(DISTINCT CASE WHEN lt_status = 'DATA_ISSUE' THEN hbl_no END) AS data_issue,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt1_days END), 2) AS avg_tt1_days,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt2_days END), 2) AS avg_tt2_days,
    ROUND(AVG(CASE WHEN lt_status = 'COMPLETE' THEN lt_tt3_days END), 2) AS avg_tt3_days
FROM x_tracking_export
WHERE id_shipper = ?
  AND id_sales = ?
  AND lt_planned_gi_at >= ?
  AND lt_planned_gi_at < ?
  AND lt_lane_name IS NOT NULL
  AND TRIM(lt_lane_name) <> ''
GROUP BY lt_lane_name
ORDER BY total_shipment DESC, lt_lane_name ASC
LIMIT " . $limit;

        return $this->db->query($sql, [(string) $shipper_id, (string) $sales_id, $date_from, $date_to])->result_array();
    }

    public function get_report_runs($shipper_id = 4407, $limit = 24)
    {
        return $this->db
            ->where('shipper_id', (int) $shipper_id)
            ->order_by('period_month', 'DESC')
            ->limit((int) $limit)
            ->get('lt_monthly_report_runs')
            ->result_array();
    }

    public function get_report_run($run_id)
    {
        return $this->db
            ->where('id', (int) $run_id)
            ->get('lt_monthly_report_runs')
            ->row_array();
    }

    public function get_lane_summary($run_id)
    {
        return $this->db
            ->where('run_id', (int) $run_id)
            ->order_by('sort_no', 'ASC')
            ->get('lt_monthly_report_lane_summary')
            ->result_array();
    }

    public function get_report_details($run_id, $status = null)
    {
        $this->db->where('run_id', (int) $run_id);

        if (!empty($status)) {
            $this->db->where('lt_status', strtoupper($status));
        }

        return $this->db
            ->order_by('lane', 'ASC')
            ->order_by('planned_gi_at', 'ASC')
            ->order_by('hbl_no', 'ASC')
            ->get('lt_monthly_report_details')
            ->result_array();
    }

    public function get_lanes($shipper_id = 4407, $date_from = null, $date_to = null, $limit = 10, $sales_id = null)
    {
        return $this->get_live_top_lanes($shipper_id, $date_from, $date_to, $limit, $sales_id);
    }

    private function normalize_sales_id($sales_id = null)
    {
        if ($sales_id === null || $sales_id === '') {
            return (int) $this->default_sales_id;
        }

        $sales_id = (int) $sales_id;
        return $sales_id > 0 ? $sales_id : (int) $this->default_sales_id;
    }

    private function normalize_date($value, $default)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $default;
        }

        return date('Y-m-d', $ts);
    }

    private function normalize_period($period = null)
    {
        $period = trim((string) $period);

        if ($period === '') {
            return date('Y-m', strtotime('first day of last month'));
        }

        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $period;
        }

        $ts = strtotime($period . '-01');
        if ($ts === false) {
            return date('Y-m', strtotime('first day of last month'));
        }

        return date('Y-m', $ts);
    }
}
