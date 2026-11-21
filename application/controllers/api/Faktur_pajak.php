<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Faktur_pajak extends RestController {

    public function __construct() {
        parent::__construct();
    }

    public function index_get() {
        // 1. Tangkap invoice_no dari React (?invoice_no=INFP/2606294)
        $invoice_no = $this->get('invoice_no');

        if (empty($invoice_no)) {
            return $this->response([
                'status'  => false,
                'message' => 'Parameter invoice_no wajib disertakan.'
            ], RestController::HTTP_BAD_REQUEST);
        }

        // 2. Bersihkan slash dan tambahkan .pdf (INFP/2606294 -> INFP2606294.pdf)
        $clean_invoice_no = str_replace('/', '', $invoice_no);
        $filename = $clean_invoice_no . '.pdf';

        // 3. PASTE URL POWER AUTOMATE ANDA DI SINI
        $power_automate_url = "https://defaultbf818f7939104b4c8ccd7b4456e889.19.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/120afcbe2dee4dcbb5c3710247630470/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=d1wiYXroGhTM73u0-Z6t1GMfqaSceiOXHeTUD2mpbvI";

        // 4. Lakukan cURL ke Power Automate
        $ch = curl_init($power_automate_url);
        $payload = json_encode(['filename' => $filename]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Eksekusi cURL
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 5. Cek respon dari Power Automate
        if ($http_code == 200 && $response) {
            $pa_data = json_decode($response, true);
            
            if (isset($pa_data['file_url'])) {
                // Sukses! Kembalikan link ke React
                return $this->response([
                    'status'  => true,
                    'message' => 'Faktur pajak ditemukan.',
                    'data'    => [
                        'file_url' => $pa_data['file_url']
                    ]
                ], RestController::HTTP_OK);
            }
        }

        // Jika Power Automate mengembalikan error (misal file tidak ada di folder OneDrive)
        return $this->response([
            'status'  => false,
            'message' => 'Faktur pajak belum tersedia, silakan hubungi finance kami.'
        ], RestController::HTTP_NOT_FOUND);
    }
}