<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_client
{
    protected $CI;
    protected $cfg;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->helper('transvoyant');
        $this->CI->load->model('Transvoyant_token_model');

        $this->CI->config->load('transvoyant');
        $this->cfg = $this->CI->config->item('transvoyant');

        if (!is_array($this->cfg)) {
            throw new Exception('Config transvoyant tidak terbaca atau formatnya salah');
        }

        $required = ['env', 'tenant', 'client_id', 'client_secret', 'base_urls'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $this->cfg)) {
                throw new Exception('Config transvoyant belum lengkap. Key hilang: ' . $key);
            }
        }
    }

    protected function get_env()
    {
        return $this->cfg['env'];
    }

    protected function get_tenant()
    {
        return $this->cfg['tenant'];
    }

    protected function get_timeout()
    {
        return (int) ($this->cfg['timeout'] ?? 30);
    }

    protected function get_base_url()
	{
		$env = $this->get_env();
		$baseUrl = $this->cfg['base_urls'][$env] ?? null;
		return rtrim((string)$baseUrl, '/');
	}

    public function get_valid_token()
    {
        $env    = $this->get_env();
        $tenant = $this->get_tenant();
        $buffer = (int) ($this->cfg['token_buffer_seconds'] ?? 120);

        $row = $this->CI->Transvoyant_token_model->get_active_token($env, $tenant);

        if ($row) {
            $expiresAt = strtotime($row['expires_at']);
            if ($expiresAt !== false && $expiresAt > (time() + $buffer)) {
                return [
                    'success' => true,
                    'token'   => $row['access_token'],
                    'source'  => 'db',
                ];
            }
        }

        $newToken = $this->request_new_token();
        if (!$newToken['success']) {
            return $newToken;
        }

        $tokenData  = $newToken['data'];
        $issuedAt   = date('Y-m-d H:i:s');
        $expiresIn  = (int) ($tokenData['expires_in'] ?? 3600);
        $expiresAt  = date('Y-m-d H:i:s', time() + $expiresIn);

        $this->CI->db->trans_start();

        $this->CI->Transvoyant_token_model->deactivate_tokens($env, $tenant);

        $this->CI->Transvoyant_token_model->insert_token([
            'env'               => $env,
            'tenant_identifier' => $tenant,
            'access_token'      => $tokenData['access_token'],
            'token_type'        => $tokenData['token_type'] ?? 'bearer',
            'expires_in'        => $expiresIn,
            'issued_at'         => $issuedAt,
            'expires_at'        => $expiresAt,
            'tenant_id'         => $tokenData['tenantId'] ?? null,
            'tenant_name'       => $tokenData['tenantName'] ?? null,
            'client_name'       => $tokenData['clientName'] ?? null,
            'jti'               => $tokenData['jti'] ?? null,
            'is_active'         => 1,
            'created_at'        => $issuedAt,
            'updated_at'        => $issuedAt,
        ]);

        $this->CI->db->trans_complete();

        if ($this->CI->db->trans_status() === FALSE) {
            return [
                'success' => false,
                'message' => 'Gagal simpan token ke database',
            ];
        }

        return [
            'success' => true,
            'token'   => $tokenData['access_token'],
            'source'  => 'api',
        ];
    }

    protected function request_new_token()
{
    $baseUrl = rtrim($this->get_base_url(), '/');
    $url = $baseUrl . '/uaa/v1/oauth/token?grant_type=client_credentials';

    $clientId     = trim($this->cfg['client_id']);
    $clientSecret = trim($this->cfg['client_secret']);

    $basicAuth = base64_encode($clientId . ':' . $clientSecret);

    log_message('error', 'TV TOKEN URL: ' . $url);
    log_message('error', 'TV TOKEN ENV: ' . $this->cfg['env']);
    log_message('error', 'TV TOKEN CLIENT_ID: ' . $clientId);

    $headers = [
        'Authorization: Basic ' . $basicAuth,
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: 0',
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $this->get_timeout(),
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response   = curl_exec($ch);
    $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlErr    = curl_error($ch);

    curl_close($ch);

    if ($curlErr) {
        log_message('error', 'TV TOKEN CURL ERROR: ' . $curlErr);
        return [
            'success' => false,
            'message' => 'Curl token error: ' . $curlErr,
        ];
    }

    $rawHeader = substr($response, 0, $headerSize);
    $rawBody   = substr($response, $headerSize);

    log_message('error', 'TV TOKEN HTTP_CODE: ' . $httpCode);
    log_message('error', 'TV TOKEN RESPONSE HEADER: ' . $rawHeader);
    log_message('error', 'TV TOKEN RESPONSE BODY: ' . $rawBody);

    $json = json_decode($rawBody, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'success'   => false,
            'message'   => 'Token request failed',
            'http_code' => $httpCode,
            'body'      => $rawBody,
        ];
    }

    if (!is_array($json) || empty($json['access_token'])) {
        return [
            'success' => false,
            'message' => 'Response token tidak valid',
            'body'    => $rawBody,
        ];
    }

    return [
        'success' => true,
        'data'    => $json,
    ];
}

    protected function http_json_request($method, $url, array $payload)
{
    $tokenResult = $this->get_valid_token();
    if (!$tokenResult['success']) {
        return [
            'success' => false,
            'stage'   => 'token',
            'message' => $tokenResult['message'] ?? 'Failed to get token',
            'http_code' => $tokenResult['http_code'] ?? null,
            'body'    => $tokenResult['body'] ?? null,
        ];
    }

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $tokenResult['token'],
        ],
        CURLOPT_POSTFIELDS     => $jsonPayload,
        CURLOPT_TIMEOUT        => $this->get_timeout(),
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response   = curl_exec($ch);
    $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlErr    = curl_error($ch);

    curl_close($ch);

    if ($curlErr) {
        return [
            'success'   => false,
            'stage'     => 'request',
            'http_code' => 0,
            'message'   => 'HTTP request failed: ' . $curlErr,
            'body'      => $curlErr,
        ];
    }

    $rawHeader = substr($response, 0, $headerSize);
    $rawBody   = substr($response, $headerSize);

    return [
        'success'   => ($httpCode >= 200 && $httpCode < 300),
        'stage'     => 'request',
        'http_code' => $httpCode,
        'body'      => $rawBody,
        'header'    => $rawHeader,
    ];
}

   public function patch_attributes(array $payload)
	{
		if (empty($payload['customerReferenceNumber'])) {
			return [
				'success' => false,
				'message' => 'customerReferenceNumber wajib',
			];
		}

		if (empty($payload['carrier'])) {
			return [
				'success' => false,
				'message' => 'carrier wajib',
			];
		}

		$url = $this->get_base_url() . '/' . $this->get_tenant() . '/v1/data/ent/shipments/';

		$result = $this->http_json_request('PATCH', $url, $payload);

		if (($result['http_code'] ?? 0) === 401) {
			$this->CI->Transvoyant_token_model->deactivate_tokens($this->get_env(), $this->get_tenant());
			$result = $this->http_json_request('PATCH', $url, $payload);
		}

		$result['endpoint'] = '/' . $this->get_tenant() . '/v1/data/ent/shipments/';
		return $result;
	}

    public function post_event(array $payload)
	{
		if (empty($payload['trackingNumber'])) {
			return [
				'success' => false,
				'message' => 'trackingNumber wajib',
			];
		}

		if (empty($payload['code'])) {
			return [
				'success' => false,
				'message' => 'code wajib',
			];
		}

		if (empty($payload['timestamp'])) {
			return [
				'success' => false,
				'message' => 'timestamp wajib',
			];
		}

		$url = $this->get_base_url() . '/' . $this->get_tenant() . '/v1/data/eco/shipments/events/';

		$result = $this->http_json_request('POST', $url, $payload);

		if (($result['http_code'] ?? 0) === 401) {
			$this->CI->Transvoyant_token_model->deactivate_tokens($this->get_env(), $this->get_tenant());
			$result = $this->http_json_request('POST', $url, $payload);
		}

		$result['endpoint'] = '/' . $this->get_tenant() . '/v1/data/eco/shipments/events/';
		return $result;
	}
}