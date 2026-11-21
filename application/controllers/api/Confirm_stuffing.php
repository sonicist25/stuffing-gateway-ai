<?php
use chriskacerguis\RestServer\RestController;

class Confirm_stuffing extends RestController
{
    private $valid_api_key = 'N7xK2pQ9rT4vW8yZbC1dE5fG'; 
    private $received_key  = ''; 

    public function __construct()
    {
        // 1. AMBIL DIAM-DIAM & SEMBUNYIKAN HEADER
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $this->received_key = $_SERVER['HTTP_X_API_KEY'];
            unset($_SERVER['HTTP_X_API_KEY']); 
        }

        // 2. Beritahu library untuk tidak mewajibkan key di endpoint ini (Method POST)
        $this->methods['update_actual_stuffing_post']['key'] = false;
		$this->methods['cancel_actual_stuffing_post']['key'] = false;
        
        parent::__construct();
        
        // Load model yang dibutuhkan
        $this->load->database();
        $this->load->model('Timeline_model');
        // $this->load->model('api/M_job_list'); // Sesuai kebutuhan file Anda
    }

    public function update_actual_stuffing_post()
    {
        // 1. Validasi API Key Manual
        if ($this->received_key !== $this->valid_api_key) {
            return $this->response([
                'status' => false,
                'message' => 'Unauthorized / API Key Salah!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        // 2. Tangkap parameter menggunakan built-in REST Server
        $kode_ref    = $this->post('kode_ref');
        $actual_date = $this->post('actual_stuffing_date');
        $user_gudang = $this->post('user_gudang');

        // 3. Validasi input wajib
        if (empty($kode_ref) || empty($actual_date)) {
            return $this->response([
                'status' => false,
                'message' => 'Parameter kode_ref dan actual_stuffing_date wajib dikirim!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        // 4. Normalisasi format tanggal (Ubah / menjadi -)
        $parse_date = str_replace('/', '-', $actual_date);
        $formatted_date = date('Y-m-d', strtotime($parse_date));
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        // 5. Cari Master Job di database
        $this->db->select('sp_id, stuffing_date');
        $master = $this->db->get_where('trnstuffingplan', ['kode_ref' => $kode_ref])->row();

        if (!$master) {
            return $this->response([
                'status' => false,
                'message' => 'Master Job tidak ditemukan di sistem!'
            ], RestController::HTTP_NOT_FOUND);
        }

        $old_date = $master->stuffing_date;

        // 6. Update tabel utama
        $this->db->where('kode_ref', $kode_ref);
        $update = $this->db->update('trnstuffingplan', ['stuffing_date' => $formatted_date]);

        if ($update) {
            // 7. Catat log ke Timeline Audit
            $is_future = strtotime($formatted_date) > strtotime($today);
            $remarks = $is_future 
                ? '⚠️ [ESTIMASI/MASA DEPAN] Rencana stuffing dijadwalkan pada: ' . date('d M Y', strtotime($formatted_date))
                : 'Pelaksanaan stuffing kargo ke dalam kontainer selesai (Tanggal Aktual: ' . date('d M Y', strtotime($formatted_date)) . ')';

            $confirmed_by = !empty($user_gudang) ? 'GUDANG (' . strtoupper($user_gudang) . ')' : 'SISTEM GUDANG';

            $this->Timeline_model->record_timeline_event(
                'MASTER', 
                $kode_ref, 
                'STUFFING_EXECUTED', 
                $now, 
                $confirmed_by,
                $remarks, 
                $old_date, 
                $formatted_date
            );

            return $this->response([
                'status'  => true, 
                'message' => 'Actual Stuffing Date berhasil diupdate dan Timeline tercatat!'
            ], RestController::HTTP_OK);
        }

        return $this->response([
            'status' => false, 
            'message' => 'Gagal mengupdate data di database.'
        ], RestController::HTTP_INTERNAL_SERVER_ERROR);
    }
	
	public function cancel_actual_stuffing_post()
    {
        // 1. Validasi API Key Manual
        if ($this->received_key !== $this->valid_api_key) {
            return $this->response([
                'status' => false,
                'message' => 'Unauthorized / API Key Salah!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        // 2. Tangkap parameter
        $kode_ref    = $this->post('kode_ref');
        $user_gudang = $this->post('user_gudang');

        if (empty($kode_ref)) {
            return $this->response([
                'status' => false,
                'message' => 'Parameter kode_ref wajib dikirim!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        // 3. Pastikan Master Job ada
        $this->db->select('sp_id');
        $master = $this->db->get_where('trnstuffingplan', ['kode_ref' => $kode_ref])->row();

        if (!$master) {
            return $this->response([
                'status' => false,
                'message' => 'Master Job tidak ditemukan di sistem!'
            ], RestController::HTTP_NOT_FOUND);
        }

        // 4. Rollback: Kosongkan stuffing_date di tabel utama
        // Gunakan '' atau '0000-00-00' menyesuaikan standar struktur kolom database Anda
        $this->db->where('kode_ref', $kode_ref);
        $update = $this->db->update('trnstuffingplan', ['stuffing_date' => '']); 

        if ($update) {
            // 5. HAPUS LOG dari Timeline Audit agar UI kembali "Pending"
            $this->db->where('ref_id', $kode_ref);
            $this->db->where('ref_type', 'MASTER');
            $this->db->where('event_code', 'STUFFING_EXECUTED');
            $this->db->delete('x_timeline_audit');

            // (Opsional) Jika standar AEO/ISO mengharuskan jejak audit tidak boleh dihapus fisik, 
            // Anda bisa mematikan fungsi delete() di atas dan menggantinya dengan insert log baru:
            // $this->Timeline_model->record_timeline_event('MASTER', $kode_ref, 'STUFFING_CANCELED', date('Y-m-d H:i:s'), 'GUDANG', 'Konfirmasi stuffing dibatalkan.');

            return $this->response([
                'status'  => true, 
                'message' => 'Konfirmasi Stuffing berhasil dibatalkan dan timeline dikembalikan ke Pending!'
            ], RestController::HTTP_OK);
        }

        return $this->response([
            'status' => false, 
            'message' => 'Gagal membatalkan data di database.'
        ], RestController::HTTP_INTERNAL_SERVER_ERROR);
    }
}