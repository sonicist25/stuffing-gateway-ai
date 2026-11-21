<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Routing extends CI_Controller {
    
    public function get_port_distance() {
        $origin = $this->input->get('origin') ?? 'SGSIN'; 
        $destination = $this->input->get('destination') ?? 'NLRTM'; 

        // UBAH: Gunakan 127.0.0.1 secara eksplisit untuk mencegah masalah resolusi IPv6 localhost
        $api_url = "http://127.0.0.1:3000/api/distance?origin={$origin}&destination={$destination}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Naikkan ke 30 detik
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $routeData = json_decode($response, true);
        $json_error = json_last_error_msg();
        
        $coords = [];
        if (isset($routeData['waypoints']) && is_array($routeData['waypoints'])) {
            foreach ($routeData['waypoints'] as $pt) {
                if (isset($pt['lat']) && isset($pt['lng'])) {
                    $coords[] = [(float)$pt['lat'], (float)$pt['lng']]; 
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => !empty($coords) ? 'success' : 'failed',
            'origin' => $origin,
            'destination' => $destination,
            'total_points' => count($coords),
            'DEBUG_INFO' => [
                'target_url' => $api_url,
                'http_status_code' => $http_code,
                'curl_error' => $err ?: 'Tidak ada error cURL',
                'json_parse_status' => $json_error,
                'detected_keys_in_json' => is_array($routeData) ? array_keys($routeData) : 'Bukan Array / Kosong',
                'raw_response_preview' => substr((string)$response, 0, 300) . '... (terpotong)'
            ],
            'maritime_path' => $coords
        ], JSON_PRETTY_PRINT);
    }
}