<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class MY_Controller extends CI_Controller
{
    protected $secret_key = 'GANTI_DENGAN_SECRET_KEY_ANDA_YANG_RUMIT';

    protected $auth_required = true;
    protected $public_methods = [];
    protected $internal_api_key_methods = [];

    protected $auth_user = null;
    protected $auth_user_id = null;
    protected $auth_username = null;
    protected $auth_level = null;
    protected $auth_region = null;
    protected $auth_company = null; // backward compatible
    protected $auth_year = null;
    protected $auth_database = null;

    public function __construct()
    {
        parent::__construct();

        if (strtoupper($this->input->method(true)) === 'OPTIONS') {
            $this->_json(true, 'OK', null, null, 200);
            exit;
        }

        if ($this->_is_internal_api_key_endpoint()) {
            return;
        }

        if ($this->_is_public_endpoint()) {
            $this->decode_bearer_jwt(false);
            return;
        }

        $ok = $this->decode_bearer_jwt(true);

        if (!$ok) {
            exit;
        }

        if (empty($this->auth_region)) {
            log_message('error', 'auth_payload failed: region missing in token');

            $this->_json(false, 'REGION_NOT_FOUND_IN_TOKEN', null, [
                'auth'      => 'REGION_NOT_FOUND_IN_TOKEN',
                'region_id' => 'required',
            ], 401);
            exit;
        }
    }

    protected function _is_internal_api_key_endpoint()
    {
        $method = $this->router->fetch_method();

        if (
            empty($this->internal_api_key_methods) ||
            !in_array($method, $this->internal_api_key_methods, true)
        ) {
            return false;
        }

        $apiKeyHeader = $this->input->get_request_header('X-API-KEY', true);
        $apiKeyServer = $_SERVER['HTTP_X_API_KEY'] ?? null;
        $apiKeyConfig = $this->config->item('finance_api_key');
        $apiKey = $apiKeyHeader ?: $apiKeyServer;

        if (
            empty($apiKey) ||
            empty($apiKeyConfig) ||
            !hash_equals((string)$apiKeyConfig, (string)$apiKey)
        ) {
            log_message('error', 'internal api key failed method=' . $method);

            $this->_json(false, 'INVALID_INTERNAL_API_KEY', null, [
                'auth' => 'INVALID_INTERNAL_API_KEY',
            ], 401);
            exit;
        }

        /*
         * Internal sync tidak memiliki user region.
         * Region untuk endpoint internal WAJIB berasal dari payload trusted source
         * dan divalidasi oleh model/controller endpoint tersebut.
         */
        $this->auth_user = (object)[
            'data' => (object)[
                'user_id'   => 0,
                'username'  => 'internal-sync',
                'level'     => 0,
                'region_id' => null,
                'year'      => date('Y'),
            ],
        ];

        $this->auth_user_id = 0;
        $this->auth_username = 'internal-sync';
        $this->auth_level = 0;
        $this->auth_region = null;
        $this->auth_company = null;
        $this->auth_year = date('Y');
        $this->auth_database = null;

        log_message('info', 'internal api key accepted method=' . $method);

        return true;
    }

    protected function _is_public_endpoint()
    {
        if ($this->auth_required === false) {
            return true;
        }

        $method = $this->router->fetch_method();

        return !empty($this->public_methods) &&
            in_array($method, $this->public_methods, true);
    }

    protected function decode_bearer_jwt($required = false)
    {
        $authHeader = $this->input->get_request_header('Authorization', true);

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            if ($required) {
                log_message('error', 'auth_payload failed: missing bearer token');

                $this->_json(false, 'MISSING_TOKEN', null, [
                    'auth' => 'MISSING_TOKEN',
                ], 401);
                exit;
            }

            return false;
        }

        $token = trim(substr($authHeader, 7));

        if ($token === '') {
            if ($required) {
                log_message('error', 'auth_payload failed: empty bearer token');

                $this->_json(false, 'EMPTY_TOKEN', null, [
                    'auth' => 'EMPTY_TOKEN',
                ], 401);
                exit;
            }

            return false;
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->secret_key, 'HS256')
            );

            $data = isset($decoded->data)
                ? (array)$decoded->data
                : [];

            $this->auth_user = $decoded;
            $this->auth_user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
            $this->auth_username = $data['username'] ?? null;
            $this->auth_level = $data['level'] ?? null;
            $this->auth_region = $data['region_id'] ?? ($data['region'] ?? null);
            $this->auth_region = $this->auth_region !== null
                ? trim((string)$this->auth_region)
                : null;

            // backward compatible: jangan dipakai sebagai tenant/company selector baru.
            $this->auth_company = $this->auth_region;

            $this->auth_year = isset($data['year'])
                ? trim((string)$data['year'])
                : null;

            if ($this->is_valid_year($this->auth_year)) {
                $this->auth_database = 'stuffing_gateway_' . $this->auth_year;
                $this->db->db_select($this->auth_database);
            }

            return true;

        } catch (ExpiredException $e) {
            log_message('error', 'auth_payload failed: expired token');

            if ($required) {
                $this->_json(false, 'TOKEN_EXPIRED', null, [
                    'auth' => 'TOKEN_EXPIRED',
                ], 401);
                exit;
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'auth_payload failed: ' . $e->getMessage());

            if ($required) {
                $this->_json(false, 'INVALID_TOKEN', null, [
                    'auth' => 'INVALID_TOKEN',
                ], 401);
                exit;
            }

            return false;
        }
    }

    public function get_auth_context()
    {
        return [
            'user_id'   => $this->auth_user_id,
            'username'  => $this->auth_username,
            'level'     => $this->auth_level,
            'region_id' => $this->auth_region,
            'year'      => $this->auth_year,
            'database'  => $this->auth_database,
        ];
    }

    protected function require_auth()
    {
        if (!empty($this->auth_user)) {
            return $this->auth_user;
        }

        $this->_json(false, 'UNAUTHORIZED', null, [
            'auth' => 'UNAUTHORIZED',
        ], 401);
        exit;
    }

    protected function require_region()
    {
        $region = trim((string)$this->auth_region);

        if ($region !== '') {
            return $region;
        }

        $this->_json(false, 'REGION_NOT_FOUND_IN_TOKEN', null, [
            'region_id' => 'required',
        ], 401);
        exit;
    }

    protected function is_valid_year($year)
    {
        if (!preg_match('/^\d{4}$/', (string)$year)) {
            return false;
        }

        $yearInt = (int)$year;
        return $yearInt >= 2020 && $yearInt <= 2035;
    }

    protected function _json($status, $message, $data = null, $errors = null, $http_code = 200)
    {
        return $this->output
            ->set_status_header((int)$http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'status'  => (bool)$status,
                'message' => $message,
                'data'    => $data,
                'errors'  => $errors,
                'meta'    => [
                    'timezone'    => 'Asia/Jakarta',
                    'server_time' => date('Y-m-d H:i:s'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
