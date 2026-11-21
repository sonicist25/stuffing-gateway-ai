<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Timeline_model extends CI_Model
{
    public $dbx;

    private $audit_fields = NULL;
    private $ref_type_definition = NULL;

    public function __construct()
    {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    /**
     * Mencatat milestone berdasarkan entitas yang benar pada saat kejadian:
     * SCHEDULE, BOOKING, MASTER, atau HOUSE.
     *
     * Context opsional:
     * - region_id
     * - schedule_id
     * - schedule_type: EXPORT_DIRECT/EXPORT_VIA/IMPORT_DIRECT/IMPORT_VIA
     * - booking_code
     */
    public function record_timeline_event(
        $ref_type,
        $ref_id,
        $event_code,
        $event_date,
        $proof_document = NULL,
        $remarks = NULL,
        $old_value = NULL,
        $new_value = NULL,
        $location_name = NULL,
        array $context = array()
    ) {
        $ref_type   = strtoupper(trim((string)$ref_type));
        $ref_id     = trim((string)$ref_id);
        $event_code = strtoupper(trim((string)$event_code));

        $allowed = array('SCHEDULE', 'BOOKING', 'MASTER', 'HOUSE');
        if (!in_array($ref_type, $allowed, TRUE)) {
            return array('status' => FALSE, 'message' => 'Tipe referensi timeline tidak valid.');
        }

        if ($ref_id === '' || $event_code === '') {
            return array('status' => FALSE, 'message' => 'Referensi dan event timeline wajib diisi.');
        }

        $username = $this->session->userdata('username')
            ?: ($this->session->userdata('fullname') ?: 'SYSTEM');
        $now = date('Y-m-d H:i:s');
        $event_date = $this->normalize_datetime($event_date, $now);

        $region_id = trim((string)($context['region_id'] ?? $this->session->userdata('region_id')));
        $schedule_id = !empty($context['schedule_id']) ? (int)$context['schedule_id'] : NULL;
        $schedule_type = $this->canonical_schedule_type($context['schedule_type'] ?? '');
        $booking_code = trim((string)($context['booking_code'] ?? ''));

        // Database lama hanya memiliki enum MASTER/HOUSE. BOOKING dan SCHEDULE
        // disimpan sebagai HOUSE untuk sementara, tetapi tidak meng-update
        // x_tracking_export. ref_id dan event_code tetap menjaga identitasnya.
        $storage_ref_type = $this->supports_ref_type($ref_type)
            ? $ref_type
            : (($ref_type === 'BOOKING' || $ref_type === 'SCHEDULE') ? 'HOUSE' : $ref_type);

        // Anti-duplikasi milestone per sumber.
        $this->dbx->from('x_timeline_audit');
        $this->dbx->where('ref_type', $storage_ref_type);
        $this->dbx->where('ref_id', $ref_id);
        $this->dbx->where('event_code', $event_code);
        if ($region_id !== '' && $this->audit_has_field('region_id')) {
            $this->dbx->where('region_id', $region_id);
        }
        if ($this->dbx->count_all_results() > 0) {
            return array('status' => TRUE, 'duplicate' => TRUE, 'message' => 'Milestone sudah tercatat.');
        }

        $this->dbx->trans_begin();

        // current_status hanya milik Master/House yang sudah benar-benar ada.
        if ($ref_type === 'MASTER') {
            $this->update_current_status(
                'trnstuffingplan', 'kode_ref', $ref_id,
                $event_code, $username, $region_id
            );
        } elseif ($ref_type === 'HOUSE') {
            $this->update_current_status(
                'x_tracking_export', 'no_job', $ref_id,
                $event_code, $username, $region_id
            );
        }

        $log_data = array(
            'ref_type'       => $storage_ref_type,
            'ref_id'         => $ref_id,
            'region_id'      => $region_id !== '' ? $region_id : NULL,
            'schedule_id'    => $schedule_id,
            'schedule_type'  => $schedule_type !== '' ? $schedule_type : NULL,
            'booking_code'   => $booking_code !== '' ? $booking_code : NULL,
            'event_code'     => $event_code,
            'event_date'     => $event_date,
            'confirmed_by'   => $username,
            'created_at'     => $now,
            'remarks'        => $remarks,
            'old_value'      => $old_value,
            'new_value'      => $new_value,
            'location_name'  => $location_name,
            'ip_address'     => $this->input->ip_address(),
            'proof_document' => $proof_document
        );

        $log_data = $this->filter_audit_columns($log_data);
        $inserted = $this->dbx->insert('x_timeline_audit', $log_data);
        $id_log = $inserted ? (int)$this->dbx->insert_id() : 0;

        if (!$inserted || $this->dbx->trans_status() === FALSE) {
            $error = $this->dbx->error();
            $this->dbx->trans_rollback();
            log_message('error', 'TIMELINE_INSERT_FAILED '.json_encode($error));
            return array('status' => FALSE, 'message' => 'Gagal memperbarui timeline database.');
        }

        $this->dbx->trans_commit();

        return array(
            'status' => TRUE,
            'id_log' => $id_log,
            'stored_ref_type' => $storage_ref_type,
            'message' => 'Timeline berhasil diperbarui.'
        );
    }

    /**
     * Nama lama dipertahankan agar controller schedule lama tetap kompatibel.
     * Sekarang event disimpan ke sumber SCHEDULE, bukan hanya kepada Master Job
     * yang kebetulan sudah ada saat schedule dibuat.
     */
    public function sync_schedule_events_to_master(
        $schedule_id,
        $schedule_type,
        $is_new_schedule,
        $do_file_token = NULL
    ) {
        $schedule_id = (int)$schedule_id;
        $schedule_type = $this->canonical_schedule_type($schedule_type);

        if ($schedule_id <= 0) {
            return FALSE;
        }

        $ref_id = 'SCHEDULE:'.$schedule_type.':'.$schedule_id;
        $context = array(
            'region_id' => $this->session->userdata('region_id'),
            'schedule_id' => $schedule_id,
            'schedule_type' => $schedule_type
        );

        if ($is_new_schedule) {
            $this->record_timeline_event(
                'SCHEDULE', $ref_id, 'SCHEDULE_PLANNED', date('Y-m-d H:i:s'),
                NULL, 'Schedule sudah dibuat (Schedule ID: '.$schedule_id.')',
                NULL, NULL, NULL, $context
            );
        }

        if (!empty($do_file_token)) {
            $this->record_timeline_event(
                'SCHEDULE', $ref_id, 'DO_RECEIVED', date('Y-m-d H:i:s'),
                'uploads/schedule_do/'.$do_file_token,
                'Delivery Order (DO) Pelayaran berhasil di-upload ke sistem',
                NULL, NULL, NULL, $context
            );
        }

        return TRUE;
    }

    /**
     * Gabungkan event Master + Schedule + Booking untuk sidebar Master Job.
     * Data lama BOOKING_RECEIVED (HOUSE + booking_code) tetap dibaca.
     */
    public function get_master_timeline_events(
        $kode_ref,
        $schedule_id,
        $schedule_type,
        array $booking_codes = array(),
        $region_id = ''
    ) {
        $events = array();
        $seen = array();
        $kode_ref = trim((string)$kode_ref);
        $schedule_id = (int)$schedule_id;
        $schedule_type = $this->canonical_schedule_type($schedule_type);
        $region_id = trim((string)$region_id);

        // Event setelah Master Job ada.
        $this->dbx->from('x_timeline_audit');
        $this->dbx->where('ref_type', 'MASTER');
        $this->dbx->where('ref_id', $kode_ref);
        $this->apply_audit_region($region_id);
        $this->append_events($events, $seen, $this->dbx->get()->result());

        // Event booking sebelum Master Job ada.
        $booking_codes = array_values(array_unique(array_filter(array_map('trim', $booking_codes))));
        if (!empty($booking_codes)) {
            $this->dbx->from('x_timeline_audit');
            $this->dbx->where('event_code', 'BOOKING_RECEIVED');
            $this->dbx->where_in('ref_type', array('BOOKING', 'HOUSE'));
            $this->dbx->group_start();
            if ($this->audit_has_field('booking_code')) {
                $this->dbx->where_in('booking_code', $booking_codes);
                $this->dbx->or_where_in('ref_id', $booking_codes);
            } else {
                $this->dbx->where_in('ref_id', $booking_codes);
            }
            $this->dbx->group_end();
            $this->apply_audit_region($region_id);
            $this->append_events($events, $seen, $this->dbx->get()->result());
        }

        // Event schedule sebelum Master Job ada.
        if ($schedule_id > 0) {
            $schedule_ref = 'SCHEDULE:'.$schedule_type.':'.$schedule_id;
            $this->dbx->from('x_timeline_audit');
            $this->dbx->where_in('event_code', array('SCHEDULE_PLANNED', 'DO_RECEIVED'));
            $this->dbx->where_in('ref_type', array('SCHEDULE', 'HOUSE'));
            $this->dbx->group_start();
            if ($this->audit_has_field('schedule_id')) {
                $this->dbx->where('schedule_id', $schedule_id);
                if ($schedule_type !== '' && $this->audit_has_field('schedule_type')) {
                    $this->dbx->where('schedule_type', $schedule_type);
                }
                $this->dbx->or_where('ref_id', $schedule_ref);
            } else {
                $this->dbx->where('ref_id', $schedule_ref);
            }
            $this->dbx->group_end();
            $this->apply_audit_region($region_id);
            $this->append_events($events, $seen, $this->dbx->get()->result());
        }

        usort($events, function ($a, $b) {
            return strcmp((string)$a->event_date, (string)$b->event_date);
        });

        return $events;
    }

    /**
     * Fallback untuk schedule lama yang belum memiliki audit trail.
     */
    public function get_schedule_milestone($schedule_id, $schedule_type, $region_id = '')
    {
        $schedule_id = (int)$schedule_id;
        $schedule_type = $this->canonical_schedule_type($schedule_type);
        $region_id = trim((string)$region_id);

        if ($schedule_id <= 0) {
            return NULL;
        }

        $table_map = array(
            'EXPORT_DIRECT' => 'tbl_schedules_direct',
            'EXPORT_VIA' => 'tbl_schedules_via',
            'IMPORT_DIRECT' => 'tbl_schedules_direct_import',
            'IMPORT_VIA' => 'tbl_schedules_via_import'
        );

        $table = $table_map[$schedule_type] ?? NULL;
        if (!$table || !$this->dbx->table_exists($table) || !$this->dbx->field_exists('id', $table)) {
            return NULL;
        }

        $this->dbx->where('id', $schedule_id);
        if ($region_id !== '' && $this->dbx->field_exists('region_id', $table)) {
            $this->dbx->where('region_id', $region_id);
        }
        $row = $this->dbx->get($table)->row();

        if (!$row) {
            return NULL;
        }

        $event_date = NULL;
        foreach (array('created_at', 'createdDtm', 'created', 'create_date', 'updated_at', 'updatedDtm') as $field) {
            if (property_exists($row, $field) && !empty($row->$field)) {
                $event_date = $this->normalize_datetime($row->$field, NULL);
                if ($event_date !== NULL) {
                    break;
                }
            }
        }

        return (object)array(
            'id_log' => 0,
            'ref_type' => 'SCHEDULE',
            'ref_id' => 'SCHEDULE:'.$schedule_type.':'.$schedule_id,
            'event_code' => 'SCHEDULE_PLANNED',
            'event_date' => $event_date,
            'confirmed_by' => 'SYSTEM',
            'remarks' => 'Schedule sudah dibuat (Schedule ID: '.$schedule_id.')',
            'proof_document' => NULL
        );
    }

    public function canonical_schedule_type($value)
    {
        $value = strtoupper(trim((string)$value));
        if ($value === '') {
            return '';
        }
        $is_import = strpos($value, 'IMPORT') !== FALSE;
        $is_via = strpos($value, 'VIA') !== FALSE;

        return ($is_import ? 'IMPORT_' : 'EXPORT_').($is_via ? 'VIA' : 'DIRECT');
    }

    private function update_current_status($table, $key, $value, $event_code, $username, $region_id)
    {
        if (!$this->dbx->table_exists($table) || !$this->dbx->field_exists('current_status', $table)) {
            return;
        }

        $update = array('current_status' => $event_code);
        if ($this->dbx->field_exists('updated_by', $table)) {
            $update['updated_by'] = $username;
        }
        if ($this->dbx->field_exists('updated_at', $table)) {
            $update['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->dbx->where($key, $value);
        if ($region_id !== '' && $this->dbx->field_exists('region_id', $table)) {
            $this->dbx->where('region_id', $region_id);
        }
        $this->dbx->update($table, $update);
    }

    private function append_events(array &$events, array &$seen, array $rows)
    {
        foreach ($rows as $row) {
            $key = !empty($row->id_log)
                ? 'ID:'.$row->id_log
                : strtoupper($row->ref_type.'|'.$row->ref_id.'|'.$row->event_code.'|'.$row->event_date);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = TRUE;
            $events[] = $row;
        }
    }

    private function apply_audit_region($region_id)
    {
        if ($region_id !== '' && $this->audit_has_field('region_id')) {
            $this->dbx->where('region_id', $region_id);
        }
    }

    private function filter_audit_columns(array $data)
    {
        return array_intersect_key($data, array_flip($this->audit_fields()));
    }

    private function audit_has_field($field)
    {
        return in_array($field, $this->audit_fields(), TRUE);
    }

    private function audit_fields()
    {
        if ($this->audit_fields === NULL) {
            $this->audit_fields = $this->dbx->list_fields('x_timeline_audit');
        }
        return $this->audit_fields;
    }

    private function supports_ref_type($ref_type)
    {
        if ($this->ref_type_definition === NULL) {
            $row = $this->dbx
                ->query("SHOW COLUMNS FROM `x_timeline_audit` LIKE 'ref_type'")
                ->row_array();
            $this->ref_type_definition = strtoupper((string)($row['Type'] ?? ''));
        }

        return strpos($this->ref_type_definition, "'".strtoupper($ref_type)."'") !== FALSE;
    }

    private function normalize_datetime($value, $fallback = NULL)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return $fallback;
        }

        $timestamp = strtotime($value);
        return $timestamp !== FALSE ? date('Y-m-d H:i:s', $timestamp) : $fallback;
    }
}
