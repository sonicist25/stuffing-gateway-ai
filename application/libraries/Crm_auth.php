<?php defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Crm_auth {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('crm', true);
    }

    /**
     * Return decoded user payload: array('user_id'=>..., 'region_id'=>..., 'management'=>..., ...)
     * Throw Exception kalau gagal.
     */
    public function require_user()
    {
        $apiKey = $this->CI->input->get_request_header('x-api-key', true);
        $expected = $this->CI->config->item('crm_api_key', 'crm');
        if (!$apiKey || $apiKey !== $expected) {
            throw new Exception('Invalid API Key');
        }

        $auth = $this->CI->input->get_request_header('authorization', true);
        if (!$auth) throw new Exception('Missing Authorization header');

        if (!preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
            throw new Exception('Invalid Authorization format');
        }

        $token = trim($m[1]);
        if ($token === '') throw new Exception('Empty token');

        $secret = $this->CI->config->item('jwt_access_secret', 'crm');
        $algo   = $this->CI->config->item('jwt_algo', 'crm') ?: 'HS256';

        try {
            $decoded = JWT::decode($token, new Key($secret, $algo));
        } catch (Throwable $e) {
             // balas JSON 401, bukan throw Exception
			$this->CI->output
				->set_status_header(401)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Invalid/expired token'
				]));
			exit; // stop eksekusi

        }

        $u = isset($decoded->data) ? (array)$decoded->data : null;
        if (!$u || empty($u['user_id'])) {
            throw new Exception('Token payload missing user data');
        }

        // normalisasi tipe
        $u['user_id'] = (int)$u['user_id'];
        $u['management'] = isset($u['management']) ? (int)$u['management'] : 0;

        return $u;
    }
}
