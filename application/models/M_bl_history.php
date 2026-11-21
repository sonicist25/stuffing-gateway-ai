<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * M_bl_history
 * ------------------------------------------------------------------
 * Menyimpan status FINAL dan histori perubahan form Bill of Lading.
 *
 * Sengaja dibuat sebagai tabel BARU (x_bl_status, x_bl_history),
 * terpisah dari trnstuffingplandetail_audit, supaya tidak mengubah
 * struktur/pemakaian tabel itu yang sekarang sudah dipakai untuk
 * fitur "pindah job".
 *
 * id_bl yang dipakai di sini SAMA dengan sp_detail_id / detail_id
 * yang sudah dipakai di seluruh modul BL (lihat x_form_bl.id_bl).
 * ------------------------------------------------------------------
 */
class M_bl_history extends CI_Model
{
    public $dbx;

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
     * Label field yang enak dibaca manusia untuk histori.
     * Tambahkan baris di sini kalau ada field baru yang mau dilacak
     * dari save_detail() / save_front().
     */
    public function field_labels()
    {
        return array(
            // --- dari save_detail() ---
            'desc_goods'        => 'Description of Goods',
            'note'              => 'Note',
            'marking_nos'       => 'Marking & Nos',
            'as_agent'          => 'As Agent',
            'backup_date'       => 'Backup Date',
            'bl_type'           => 'B/L Type',
            'payable'           => 'Payable',
            'diff_status_1'     => 'Other Status (1)',
            'diff_status_2'     => 'Other Status (2)',
            'hb_l'              => 'B/L No',
            'consignee'         => 'Consignee',
            'consignee_add'     => 'Consignee Address',
            'notify'            => 'Notify',
            'notify_add'        => 'Notify Address',
            'port_of_loading'   => 'Port of Loading',
            'port_of_discharge' => 'Port of Discharge',
            'port_of_receive'   => 'Port of Receive',
            'port_of_delivery'  => 'Port of Delivery',
            'netto'             => 'Netto',
            'express_release'          => 'Express Release',
            'shipped_on_board'         => 'Shipped On Board',
            'net_weight_tidak_dicetak' => 'Net Weight tidak dicetak',
            'cetak_jml_doc_origi'      => 'Original BL',
            'cy_cy'             => 'CY/CY',
            'cfs_cfs'           => 'CFS/CFS',
            'job_no'            => 'Job No',

            // --- dari save_front() ---
            'location'               => 'Location',
            'trucking_co'            => 'Trucking Co',
            'no_from_shipper'        => 'S/I No from Shipper',
            'manifest_no'            => 'Manifest No',
            'feeder_vessel'          => 'Feeder Vessel',
            'feeder_vessel_etd'      => 'Feeder Vessel ETD',
            'feeder_vessel_eta'      => 'Feeder Vessel ETA',
            'feeder_vessel_etd_date' => 'Feeder Vessel ETD Date',
            'feeder_vessel_eta_date' => 'Feeder Vessel ETA Date',
            'snd_vessel'             => 'Connecting Vessel',
            'snd_vessel_etd'         => 'Connecting Vessel ETD',
            'snd_vessel_eta'         => 'Connecting Vessel ETA',
            'snd_vessel_etd_date'    => 'Connecting Vessel ETD Date',
            'snd_vessel_eta_date'    => 'Connecting Vessel ETA Date',
            'last_vessel'            => 'Last Vessel',
            'last_vessel_etd'        => 'Last Vessel ETD',
            'last_vessel_eta'        => 'Last Vessel ETA',
            'last_vessel_etd_date'   => 'Last Vessel ETD Date',
            'last_vessel_eta_date'   => 'Last Vessel ETA Date',
            'last_snd_vessel'            => 'Last Connecting Vessel',
            'last_snd_vessel_etd'        => 'Last Connecting Vessel ETD',
            'last_snd_vessel_eta'        => 'Last Connecting Vessel ETA',
            'last_snd_vessel_etd_date'   => 'Last Connecting Vessel ETD Date',
            'last_snd_vessel_eta_date'   => 'Last Connecting Vessel ETA Date',
            'last_count'             => 'Last Container Count',
            'date_do_release'        => 'Date DO Release',
            'no_peb'                 => 'No PEB',
            'tanggal_peb'            => 'Tanggal PEB',
            'shipper_name'           => 'Shipper Name',
            'shipper_address'        => 'Shipper Address',
            'mbl'                    => 'Master B/L',

            // --- dari Stuffing Plan (Afg_m_stuffing_plan::saving_data / manage_tabel_edit) ---
            // Kolom-kolom ini sudah diverifikasi langsung via DESCRIBE
            // trnstuffingplandetail (bukan tebakan lagi).
            'weight'                 => 'Gross Weight',
            'wh_meas'                => 'WH Measurement',
            'ship_meas'              => 'Ship Measurement',
            'chgs_weight'            => 'Chargeable Weight',
            'shipper'                => 'Shipper ID',
            'shipper_print'          => 'Shipper',
            'hbl'                    => 'HBL Flag',
            'booking_code'           => 'Booking Code',
            'piece'                  => 'Piece',
            'rate_class'             => 'Rate Class',
            'item_number'            => 'Item Number',
            'rate_per_charges'       => 'Rate/Charges',
            'total'                  => 'Total',
            'shipper_certification'  => 'Shipper Certification',
            'agent_code'             => 'Agent Code',
            'c_o'                    => 'C/O',
            'shipping_mark'          => 'Shipping Mark',
            'no_pack_qty'            => 'No. of Packages (Qty)',
            'no_pack_unit'           => 'No. of Packages (Unit)',
            'shipment_request_id'    => 'Shipment Request ID',
            'wg_ctn'                 => 'Weight/Container',
            'doc'                    => 'Doc',
            'isN'                    => 'Negotiable B/L',
            'isOptCost'              => 'Optional Cost',
            'status_selling'         => 'Status Selling',
            'isSpotRate'             => 'Spot Rate',
            'isLock'                 => 'Lock Status',
            'sales'                  => 'Sales',
            'shipp_co'               => 'Shipping Co',
            'cost_meas'              => 'Cost Measurement',
            'wh_agent'               => 'WH Agent',
            'bl_meas'               => 'Measurement',
            'destination'            => 'Destination',
        );
    }

    // ===========================================================
    // STATUS (x_bl_status)
    // ===========================================================

    public function get_status($id_bl)
    {
        $q = $this->dbx->get_where('x_bl_status', array('id_bl' => $id_bl));
        if ($q->num_rows() > 0) {
            $row = $q->row_array();
            $row['is_final'] = (int) $row['is_final'];
            return $row;
        }
        return array(
            'id_bl'         => $id_bl,
            'created_by'    => null,
            'created_at'    => null,
            'is_final'      => 0,
            'finalized_by'  => null,
            'finalized_at'  => null,
            'delivered_by'  => null,
            'delivered_at'  => null,
            'delivered_to'  => null,
            'delivered_via' => null,
        );
    }

    public function is_final($id_bl)
    {
        $row = $this->get_status($id_bl);
        return $row['is_final'] == 1;
    }

    /**
     * Pastikan ada baris status + histori "created" untuk BL ini.
     * Aman dipanggil berkali-kali (no-op kalau sudah ada).
     */
    public function ensure_created($id_bl, $user)
    {
        if (!$id_bl) {
            return;
        }
        $q = $this->dbx->get_where('x_bl_status', array('id_bl' => $id_bl));
        if ($q->num_rows() > 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->dbx->insert('x_bl_status', array(
            'id_bl'      => $id_bl,
            'created_by' => $user,
            'created_at' => $now,
            'is_final'   => 0,
        ));
        $this->add_history($id_bl, 'created', null, null, null, null, $user, $now);
    }

    public function finalize($id_bl, $user)
    {
        if ($this->is_final($id_bl)) {
            return 0; // sudah final sebelumnya
        }
        $this->ensure_created($id_bl, $user);

        $now = date('Y-m-d H:i:s');
        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->update('x_bl_status', array(
            'is_final'     => 1,
            'finalized_by' => $user,
            'finalized_at' => $now,
        ));
        $this->add_history($id_bl, 'finalized', null, null, null, null, $user, $now);
        return 1;
    }

    public function cancel_final($id_bl, $user)
    {
        if (!$this->is_final($id_bl)) {
            return 0; // memang belum final
        }

        // Titik untuk pembatasan role kalau nanti diperlukan, misal:
        // if ($this->session->userdata('level') > 2) { return 0; }

        $now = date('Y-m-d H:i:s');
        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->update('x_bl_status', array(
            'is_final'     => 0,
            'finalized_by' => null,
            'finalized_at' => null,
        ));
        $this->add_history($id_bl, 'cancel_final', null, null, null, null, $user, $now);
        return 1;
    }

    /**
     * Catat satu kejadian penyerahan BL ke customer.
     * Disimpan lengkap di x_bl_delivery (bisa lebih dari satu kali,
     * misal dikirim ulang), dan snapshot TERAKHIR-nya disalin ke
     * x_bl_status supaya gampang ditampilkan di badge/summary.
     *
     * $delivery_date : tanggal BL diserahkan (input manual, format Y-m-d)
     * $delivered_to  : diserahkan kepada siapa (nama orang/perusahaan)
     * $delivered_via : 'Messenger' | 'Courier' | 'Email'
     */
    public function add_delivery($id_bl, $delivery_date, $delivered_to, $delivered_via, $user)
    {
        if (!$id_bl) {
            return 0;
        }
        $this->ensure_created($id_bl, $user);

        $now = date('Y-m-d H:i:s');

        $this->dbx->insert('x_bl_delivery', array(
            'id_bl'         => $id_bl,
            'delivery_date' => $delivery_date,
            'delivered_to'  => $delivered_to,
            'delivered_via' => $delivered_via,
            'recorded_by'   => $user,
            'recorded_at'   => $now,
        ));

        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->update('x_bl_status', array(
            'delivered_by'  => $user,
            'delivered_at'  => $delivery_date,
            'delivered_to'  => $delivered_to,
            'delivered_via' => $delivered_via,
        ));

        $summary = 'Kepada: ' . $delivered_to . ', Via: ' . $delivered_via . ', Tanggal: ' . $delivery_date;
        $this->add_history($id_bl, 'delivered', 'delivery', 'Diserahkan ke Customer', null, $summary, $user, $now);

        return 1;
    }

    public function get_deliveries($id_bl)
    {
        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->order_by('delivery_date', 'asc');
        $this->dbx->order_by('delivery_id', 'asc');
        return $this->dbx->get('x_bl_delivery')->result_array();
    }

    // ===========================================================
    // HISTORY (x_bl_history)
    // ===========================================================

    public function add_history($id_bl, $action, $field_name, $field_label, $old_value, $new_value, $user, $when = null)
    {
        $this->dbx->insert('x_bl_history', array(
            'id_bl'        => $id_bl,
            'action'       => $action,
            'field_name'   => $field_name,
            'field_label'  => $field_label,
            'old_value'    => $old_value,
            'new_value'    => $new_value,
            'performed_by' => $user,
            'performed_at' => $when ? $when : date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Bandingkan nilai baru ($_POST) dengan SNAPSHOT terakhir yang sudah
     * pernah kita catat (bukan baca ulang dari tbl_detail/trnstuffingplandetail).
     *
     * Kenapa begini, bukan baca ulang dari DB:
     * form ini kirim tanggal sebagai "17/08/2026", sedangkan kolom di DB
     * (mis. feeder_vessel_etd_date) tersimpan sebagai "20260817". Kalau
     * dibandingkan langsung, dua representasi itu SELALU dianggap beda
     * walau nilainya sama -> histori jadi penuh entri "berubah" palsu
     * tiap kali form disave. Dengan snapshot, yang dibandingkan selalu
     * format yang sama (format form), jadi hanya benar-benar berubah
     * yang tercatat.
     */
    public function log_field_changes($id_bl, $new_data, $user)
    {
        // Controller membutuhkan daftar nama field yang benar-benar berubah.
        // Versi lama tidak me-return apa pun sehingga in_array(..., $changed)
        // dapat menerima NULL / menimbulkan TypeError pada PHP modern.
        $changed = array();

        if (!$id_bl) {
            return $changed;
        }
        $labels = $this->field_labels();
        $now    = date('Y-m-d H:i:s');

        $this->dbx->where('id_bl', $id_bl);
        $snapRows = $this->dbx->get('x_bl_field_snapshot')->result_array();
        $snapshot = array();
        foreach ($snapRows as $r) {
            $snapshot[$r['field_name']] = $r['last_value'];
        }

        foreach ($labels as $field => $label) {
            if (!array_key_exists($field, $new_data)) {
                continue; // field ini tidak dikirim dari form yang sedang di-submit
            }

            $new_val = is_array($new_data[$field]) ? json_encode($new_data[$field]) : (string) $new_data[$field];
            $new_val = trim($new_val);

            $has_snapshot = array_key_exists($field, $snapshot);
            $old_val = $has_snapshot ? trim((string) $snapshot[$field]) : null;

            if ($has_snapshot) {
                if (strcasecmp($old_val, $new_val) !== 0) {
                    $this->add_history($id_bl, 'edited', $field, $label, $old_val, $new_val, $user, $now);
                    $this->save_snapshot($id_bl, $field, $new_val);
                    $changed[] = $field;
                }
                // sama persis -> tidak dicatat, tidak perlu update snapshot
            } else {
                // belum ada snapshot sebelumnya (baseline pertama kali) -> simpan
                // diam2 tanpa nge-log, supaya save pertama tidak memicu banjir
                // histori "berubah dari kosong" untuk semua field sekaligus.
                $this->save_snapshot($id_bl, $field, $new_val);
            }
        }

        return $changed;
    }

    /**
     * Tandai bahwa Note pernah diubah melalui form BL.
     * Tidak perlu kolom database baru: flag disimpan di x_bl_field_snapshot.
     * Histori pseudo-field "note_manual" dipertahankan agar kompatibel dengan
     * data history yang sebelumnya sudah memakai label Note Manual Override.
     */
    public function mark_note_manual($id_bl, $user)
    {
        if (!$id_bl) {
            return 0;
        }

        $q = $this->dbx->get_where('x_bl_field_snapshot', array(
            'id_bl'      => $id_bl,
            'field_name' => 'note_manual',
        ));

        $already_manual = false;
        if ($q->num_rows() > 0) {
            $already_manual = trim((string) $q->row()->last_value) === '1';
        }

        if ($already_manual) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $this->save_snapshot($id_bl, 'note_manual', '1');
        $this->add_history(
            $id_bl,
            'edited',
            'note_manual',
            'Note Manual Override',
            '0',
            '1',
            $user,
            $now
        );

        return 1;
    }

    /**
     * Dipakai bila modul lain perlu mengetahui apakah Note sudah pernah
     * disentuh melalui form BL. Ada fallback ke x_bl_history agar data lama
     * yang sudah memiliki entri note_manual tetap terbaca.
     */
    public function is_note_manual($id_bl)
    {
        if (!$id_bl) {
            return false;
        }

        $q = $this->dbx->get_where('x_bl_field_snapshot', array(
            'id_bl'      => $id_bl,
            'field_name' => 'note_manual',
        ));

        if ($q->num_rows() > 0 && trim((string) $q->row()->last_value) === '1') {
            return true;
        }

        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->where('field_name', 'note_manual');
        $this->dbx->where('new_value', '1');
        return $this->dbx->get('x_bl_history')->num_rows() > 0;
    }

    private function save_snapshot($id_bl, $field, $value)
    {
        $exists = $this->dbx->get_where('x_bl_field_snapshot', array('id_bl' => $id_bl, 'field_name' => $field))->num_rows() > 0;
        $now    = date('Y-m-d H:i:s');

        if ($exists) {
            $this->dbx->where(array('id_bl' => $id_bl, 'field_name' => $field));
            $this->dbx->update('x_bl_field_snapshot', array('last_value' => $value, 'updated_at' => $now));
        } else {
            $this->dbx->insert('x_bl_field_snapshot', array(
                'id_bl'       => $id_bl,
                'field_name'  => $field,
                'last_value'  => $value,
                'updated_at'  => $now,
            ));
        }
    }

    public function get_history($id_bl)
    {
        $this->dbx->where('id_bl', $id_bl);
        $this->dbx->order_by('performed_at', 'asc');
        $this->dbx->order_by('history_id', 'asc');
        return $this->dbx->get('x_bl_history')->result_array();
    }
}
