<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Api_Quotation extends CI_Controller
{
    private $api_key    = '0f2ca76719dedbc';
    private $api_secret = '74a2d9a12e00228';
    private $erp_base   = 'https://gatewaycontainer.jasaweb.id';
    
    // Pastikan key ini SAMA dengan yang ada di Controller Login
     private $jwt_key = "your_secret_key";

    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Sesuaikan domain production nanti
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Izinkan Header Authorization

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    /**
     * Helper: Validasi JWT dan Ambil Data User
     */
   /**
     * Helper: Validasi JWT dan Ambil Data User (Versi Aman)
     */
    private function get_user_from_token() 
    {
        // 1. Ambil Header Authorization
        $headers = $this->input->request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        // Fallback: Cek $_SERVER jika CodeIgniter gagal baca header (Sering terjadi di Apache)
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Jika Header benar-benar kosong -> Stop
        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Authorization header not found']);
            exit; 
        }

        // 2. Ekstrak Token (Inisialisasi variabel dulu supaya tidak error Undefined)
        $token = null;

        // Cek format "Bearer <token>"
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            // Jika regex gagal, asumsikan user mengirim raw token tanpa "Bearer "
            $token = $authHeader;
        }

        // Cek apakah token berhasil diambil
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Token string is empty']);
            exit;
        }

        try {
            // 3. Decode Token
            // Pastikan $this->jwt_key SAMA dengan controller Login
            $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));
            
            return $decoded->data; 

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'status' => false, 
                'message' => 'Invalid Token: ' . $e->getMessage(),
                'debug_token' => substr($token, 0, 10) . '...' // Debugging (opsional)
            ]);
            exit;
        }
    }

     private function call_erp($path, $query = [])
    {
        $token = $this->api_key . ':' . $this->api_secret;

        $url = rtrim($this->erp_base, '/') . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: token ' . $token,
            ],
            CURLOPT_TIMEOUT        => 20,
        ]);

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'cURL error: ' . $error,
            ]);
            return;
        }

        http_response_code($status ?: 200);
        echo $result;
    }
    /**
     * GET /api/quotations/list
     * Filter otomatis by JWT Shipper Name & Date > Today
     */
    public function list()
    {
        // 1. Ambil data user dari JWT (Otomatis 401 jika token salah)
        $userData = $this->get_user_from_token();
        $shipperName = $userData->shipper_name; // Ambil shipper_name dari payload login

        // 2. Tentukan Tanggal Hari Ini (Y-m-d)
        $today = date('Y-m-d');

        // 3. Susun Filter ERPNext
        // Logic:
        // - Status = Submitted
        // - Customer Name = shipper_name (User yang login)
        // - Valid Till > Hari ini (Belum expired)
        $filters = [
            ["Quotation", "status", "=", "Submitted"],
            ["Quotation", "customer_name", "=", $shipperName], 
            ["Quotation", "valid_till", ">", $today] 
        ];

        // Debugging (Opsional: lihat filter di response jika error)
        // echo json_encode($filters); exit; 

        $this->call_erp('api/resource/Quotation', [
            'filters'           => json_encode($filters),
            'fields'            => '["name", "customer_name", "valid_till", "status", "sales_person", "port_of_loading", "delivery", "incoterm", "service"]',
            'order_by'          => 'creation desc',
            'limit_page_length' => 50
        ]);
    }

    /**
     * GET /api/quotations/{quotation_no}
     * Detail satu quotation (berdasarkan "name" / nomor quotation)
     *
     * Contoh:
     *   /api/quotations/SAL-QTN-2025-00227
     */
    public function detail($quotation_no)
    {
        // decode kalau ada %2F, %20 dll dari URL
        $quotation_no = urldecode($quotation_no);

        // encode lagi untuk posisi di path URL ERP
        $encoded = rawurlencode($quotation_no);

        $this->call_erp('api/resource/Quotation/' . $encoded, [
            'fields' => '["*"]',
        ]);
    }
}
