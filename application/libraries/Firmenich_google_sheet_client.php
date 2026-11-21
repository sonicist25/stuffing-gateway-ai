<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Firmenich_google_sheet_client
{
    protected $CI;
    protected $cfg = array();

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->config->load('firmenich_kpi_google_sheet', true);
        $cfg = $this->CI->config->item('firmenich_kpi_google_sheet');

        if (is_array($cfg) && isset($cfg['firmenich_kpi_google_sheet'])) {
            $cfg = $cfg['firmenich_kpi_google_sheet'];
        }

        $this->cfg = is_array($cfg) ? $cfg : array();
    }

    /**
     * Existing KPI workbook generator.
     * Keeps the old payload contract: report_type=kpi + rows.
     */
    public function create_report(array $rows, array $options = array())
    {
        if (empty($rows)) {
            throw new RuntimeException('Tidak ada row Firmenich untuk dikirim.');
        }

        $payload = $this->base_payload($options);
        $payload['report_type'] = 'kpi';
        $payload['rows'] = array_values($rows);

        return $this->send_report($payload);
    }

    /**
     * Dedicated Pakistan monthly workbook generator.
     * Google Apps Script creates a NEW blank spreadsheet and writes ONLY PK tabs.
     */
    public function create_pakistan_report(array $pakistanMonthly, array $options = array())
    {
        $rows = isset($pakistanMonthly['rows']) && is_array($pakistanMonthly['rows'])
            ? $pakistanMonthly['rows']
            : array();

        if (empty($rows)) {
            throw new RuntimeException('Tidak ada shipment Pakistan untuk dikirim.');
        }

        $payload = $this->base_payload($options);
        $payload['report_type'] = 'pakistan_monthly';
        $payload['pakistan_monthly'] = array(
            'periods' => isset($pakistanMonthly['periods']) && is_array($pakistanMonthly['periods'])
                ? array_values($pakistanMonthly['periods'])
                : array(),
            'rows' => array_values($rows)
        );

        return $this->send_report($payload);
    }

    protected function base_payload(array $options)
    {
        return array(
            'token' => $this->api_token(),
            'report_name' => isset($options['report_name'])
                ? $options['report_name']
                : $this->default_report_name(),
            'share' => array(
                'mode' => isset($options['share_mode'])
                    ? $options['share_mode']
                    : $this->cfg_value('share_mode', 'restricted'),
                'role' => isset($options['share_role'])
                    ? $options['share_role']
                    : $this->cfg_value('share_role', 'viewer'),
                'emails' => isset($options['share_emails'])
                    ? array_values((array)$options['share_emails'])
                    : array_values((array)$this->cfg_value('share_emails', array()))
            )
        );
    }

    protected function send_report(array $payload)
    {
        $result = $this->post_json($this->web_app_url(), $payload);

        if (empty($result['status'])) {
            throw new RuntimeException(
                isset($result['message'])
                    ? $result['message']
                    : 'Google Apps Script gagal membuat report.'
            );
        }

        return $result;
    }

    protected function web_app_url()
    {
        $url = trim((string)$this->cfg_value('web_app_url'));

        if ($url === '') {
            throw new RuntimeException(
                'FIRMENICH_KPI_GSHEET_URL / web_app_url belum diisi.'
            );
        }

        return $url;
    }

    protected function api_token()
    {
        $token = trim((string)$this->cfg_value('api_token'));

        if ($token === '') {
            throw new RuntimeException(
                'FIRMENICH_KPI_GSHEET_TOKEN / api_token belum diisi.'
            );
        }

        return $token;
    }

    protected function post_json($url, array $payload)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension tidak tersedia.');
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException('Gagal encode payload Google Apps Script.');
        }

        /*
         * Apps Script ContentService may redirect POST /exec to
         * script.googleusercontent.com. Handle it explicitly:
         * 1) POST /exec, no auto-follow
         * 2) read Location
         * 3) GET Location
         */
        $first = $this->curl_request(
            $url,
            array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json'
                )
            ),
            true
        );

        $http = (int)$first['http_code'];
        $body = (string)$first['body'];
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            if ($http >= 400) {
                throw new RuntimeException(
                    'Google Apps Script HTTP '.$http.': '.
                    (isset($decoded['message']) ? $decoded['message'] : $body)
                );
            }

            return $decoded;
        }

        $location = isset($first['headers']['location'])
            ? trim((string)$first['headers']['location'])
            : '';

        if (
            in_array($http, array(301, 302, 303, 307, 308), true) &&
            $location !== ''
        ) {
            $second = $this->curl_request(
                $location,
                array(
                    CURLOPT_HTTPGET => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json'
                    )
                ),
                false
            );

            $finalHttp = (int)$second['http_code'];
            $finalBody = (string)$second['body'];
            $finalDecoded = json_decode($finalBody, true);

            if (!is_array($finalDecoded)) {
                throw new RuntimeException(
                    'Response redirect Google Apps Script bukan JSON. '.
                    'Initial HTTP '.$http.
                    ', Final HTTP '.$finalHttp.
                    ', Final URL: '.$second['effective_url'].
                    '. Body: '.substr($finalBody, 0, 500)
                );
            }

            if ($finalHttp >= 400) {
                throw new RuntimeException(
                    'Google Apps Script redirect HTTP '.$finalHttp.': '.
                    (isset($finalDecoded['message'])
                        ? $finalDecoded['message']
                        : $finalBody)
                );
            }

            return $finalDecoded;
        }

        throw new RuntimeException(
            'Response Google Apps Script bukan JSON dan tidak ada redirect valid. '.
            'HTTP '.$http.
            ', Effective URL: '.$first['effective_url'].
            '. Body: '.substr($body, 0, 500)
        );
    }

    protected function curl_request($url, array $extraOptions, $captureHeaders = false)
    {
        $headers = array();
        $ch = curl_init($url);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'GatewayCL-Firmenich-GSheet/4.3'
        );

        if ($captureHeaders) {
            $options[CURLOPT_HEADERFUNCTION] = function($ch, $line) use (&$headers) {
                $len = strlen($line);
                $line = trim($line);

                if ($line === '' || strpos($line, ':') === false) {
                    return $len;
                }

                list($name, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);

                return $len;
            };
        }

        foreach ($extraOptions as $key => $value) {
            $options[$key] = $value;
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $error = curl_error($ch);

        $info = array(
            'http_code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'effective_url' => (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'redirect_count' => (int)curl_getinfo($ch, CURLINFO_REDIRECT_COUNT),
            'content_type' => (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE)
        );

        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException(
                'Google Apps Script request gagal: '.$error
            );
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'http_code' => $info['http_code'],
            'effective_url' => $info['effective_url'],
            'redirect_count' => $info['redirect_count'],
            'content_type' => $info['content_type']
        );
    }

    protected function default_report_name()
    {
        $prefix = trim((string)$this->cfg_value(
            'report_name_prefix',
            'Firmenich KPI Report'
        ));

        return $prefix.' '.date('Y-m-d H.i');
    }

    protected function cfg_value($key, $default = null)
    {
        return array_key_exists($key, $this->cfg)
            ? $this->cfg[$key]
            : $default;
    }
}
