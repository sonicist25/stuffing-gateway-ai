<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verify extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // HMAC token based on document number (as you use in QR)
    protected function make_qr_token($docNo)
    {
        $secret = (string)$this->config->item('qr_secret');
        return hash_hmac('sha256', (string)$docNo, $secret);
    }

    protected function verify_qr_token($docNo, $token)
    {
        return hash_equals($this->make_qr_token($docNo), (string)$token);
    }

    public function invoice()
    {
        $number = (string)$this->input->get('number', true);
        $token  = (string)$this->input->get('t', true);

        $view = [
            'ok'      => false,
            'title'   => 'Invoice Verification',
            'number'  => $number,
            'message' => '',
            'data'    => null,
            'ts'      => date('Y-m-d H:i:s'),
        ];

        if ($number === '' || $token === '') {
            $view['message'] = 'Missing parameters.';
            return $this->load->view('verify_invoice', $view);
        }

        if (!$this->verify_qr_token($number, $token)) {
            $view['message'] = 'Invalid token (the link may have been modified).';
            return $this->load->view('verify_invoice', $view);
        }

        $api = $this->call_invoice_status_api($number);

        if (!$api['ok']) {
            $view['message'] = $api['error']; // already safe / generic
            return $this->load->view('verify_invoice', $view);
        }

        $view['ok']   = true;
        $view['data'] = $api['row'];

        $this->load->view('verify_invoice', $view);
    }

    protected function call_invoice_status_api($number)
    {
        $baseUrl = (string)$this->config->item('invoice_status_api_url');
        $apiKey  = (string)$this->config->item('invoice_status_api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return ['ok' => false, 'error' => 'API configuration is incomplete.'];
        }

        $url = $baseUrl
             . '?X-API-KEY=' . rawurlencode($apiKey)
             . '&number='    . rawurlencode($number);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => 'Unable to reach validation server.'];
        }
        if ($http < 200 || $http >= 300) {
            return ['ok' => false, 'error' => 'Validation server returned an error.'];
        }

        $json = json_decode($body, true);
        if (!is_array($json) || count($json) < 1) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }

        $r = $json[0];

        $row = [
            'number'      => (string)($r['number'] ?? $number),
            'date'        => (string)($r['date'] ?? ''),
            'currency'    => (string)($r['currency'] ?? ''),
            'type'    		=> (string)($r['jenis'] ?? ''),
            'tgl_bayar'   => $r['tgl_bayar'] ?? null,
            'grand_total' => $r['grand_total'] ?? null,
            'status'      => (string)($r['status'] ?? ''),
        ];

        return ['ok' => true, 'row' => $row];
    }
}
