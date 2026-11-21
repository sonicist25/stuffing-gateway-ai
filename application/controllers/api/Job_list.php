<?php
use chriskacerguis\RestServer\RestController;

class Job_list extends RestController
{
    private $valid_api_key = 'N7xK2pQ9rT4vW8yZbC1dE5fG'; 
    private $received_key  = ''; // Variabel untuk menampung key kita

    public function __construct()
    {
        // 1. AMBIL DIAM-DIAM & SEMBUNYIKAN HEADER
        // Di PHP, header 'x-api-key' dibaca sebagai 'HTTP_X_API_KEY'
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $this->received_key = $_SERVER['HTTP_X_API_KEY'];
            
            // Hapus sementara agar library RestController TIDAK BISA mendeteksinya
            unset($_SERVER['HTTP_X_API_KEY']); 
        }

        // 2. Beritahu library untuk tidak mewajibkan key di endpoint ini
        $this->methods['index_get']['key'] = false;
        
        // 3. Panggil constructor bawaan (Sekarang akan aman dan tidak diblokir)
        parent::__construct();
        
        // Load model
        $this->load->model('api/M_job_list');
    }

    // Endpoint: GET /api/job_list
    public function index_get()
    {
        // 4. Validasi secara manual menggunakan key yang kita simpan tadi
        if (empty($this->received_key) || $this->received_key !== $this->valid_api_key) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or missing x-api-key.',
            ], RestController::HTTP_UNAUTHORIZED);
        }

        // 5. Jika lolos, ambil data
        try {
            $data = $this->M_job_list->get_filtered_jobs();

            return $this->response([
                'status' => true,
                'data'   => $data,
                'meta'   => [
                    'total_jobs' => count($data)
                ],
            ], RestController::HTTP_OK);

        } catch (Throwable $e) {
            log_message('error', 'Job_list error: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Gagal mengambil daftar nomor job.',
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}