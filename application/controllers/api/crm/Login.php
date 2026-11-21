<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Login extends RestController {

    private $secret_key = "your-secret-key"; // ganti dengan secret key yang aman
    private $refresh_secret = "your-refresh-secret"; // secret khusus refresh token

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index_post() {
        $apiKey = $this->input->get_request_header('x-api-key');
        if ($apiKey !== 'gateway-fms') {
            return $this->response(['status'=>false,'message'=>'Invalid API Key'], RestController::HTTP_UNAUTHORIZED);
        }

        $username = $this->post('username');
        $password = $this->post('password');
		
		$this->db->select('mstadmin.user_id,
						   mstadmin.username,
						   mstadmin.fullname AS fullname,
						   mstadmin.email,
						   mstadmin.level,
						   mstadmin.password_encript,
						   mstadmin.region_id AS region_id,
						   refsales.fullname AS sales_fullname,
						   refsales.remark,
						   refsales.management,
						   refsales.percentage,
						   refsales.type,
						   refsales.status,
						   refsales.is_active,
						   refsales.region_id AS sales_region');
		$this->db->from('mstadmin');
		$this->db->join('refsales', 'mstadmin.user_id = refsales.user_id', 'left');
		$this->db->where('mstadmin.username', $username);
		$this->db->where('mstadmin.active', 'YES');

		$query = $this->db->get();

        $user = $query->row();
        if (!$user) return $this->response(['status'=>false,'message'=>'User tidak ditemukan'], RestController::HTTP_NOT_FOUND);

        if (!in_array($user->level,[0,1,2])) {
            return $this->response(['status'=>false,'message'=>'Level tidak diizinkan'], RestController::HTTP_FORBIDDEN);
        }

        if ($password !== $user->password_encript) {
            return $this->response(['status'=>false,'message'=>'Password salah'], RestController::HTTP_UNAUTHORIZED);
        }

        // Access token (1 jam)
        $payload_access = [
		  "iss" => "gateway-fms",
		  "iat" => time(),
		  "exp" => time() + 3600,
		  "data" => [
			"user_id"    => (int) $user->user_id,
			"username"   => (string) $user->username,
			"fullname" => (string) ($user->fullname ?? ""),
			"email"    => (string) ($user->email ?? ""),
			"level"      => (int) $user->level,
			"region_id"  => (string) $user->region_id,
			"management" => (int) ($user->management ?? 0), // 1=management, 0=non
		  ]
		];
        $access_token = JWT::encode($payload_access, $this->secret_key, 'HS256');

        // Refresh token (7 hari)
        $payload_refresh = [
            "iss"=>"gateway-fms",
            "iat"=>time(),
            "exp"=>time()+(7*24*3600),
            "data"=>[
                "user_id"=>$user->user_id
            ]
        ];
        $refresh_token = JWT::encode($payload_refresh, $this->refresh_secret, 'HS256');

        // Simpan refresh token di DB (opsional)
        $this->db->update('mstadmin', ['session_id'=>$refresh_token], ['user_id'=>$user->user_id]);

        return $this->response([
            'status'=>true,
            'message'=>'Login berhasil',
            'access_token'=>$access_token,
            'refresh_token'=>$refresh_token
        ], RestController::HTTP_OK);
    }
	
	public function refresh_post()
	{
		$apiKey = $this->input->get_request_header('x-api-key');
		if ($apiKey !== 'gateway-fms') {
			return $this->response(['status'=>false,'message'=>'Invalid API Key'], RestController::HTTP_UNAUTHORIZED);
		}

		// Terima refresh_token dari form-data / x-www-form / JSON body
		$refresh_token = $this->post('refresh_token');

		if (!$refresh_token) {
			$raw = $this->input->raw_input_stream;
			$json = json_decode($raw, true);
			if (is_array($json) && !empty($json['refresh_token'])) {
				$refresh_token = $json['refresh_token'];
			}
		}

		$refresh_token = trim((string)$refresh_token);
		if ($refresh_token === '') {
			return $this->response(['status'=>false,'message'=>'Missing refresh_token'], RestController::HTTP_BAD_REQUEST);
		}

		// Decode refresh JWT
		try {
			$decoded = JWT::decode($refresh_token, new Key($this->refresh_secret, 'HS256'));
			$user_id = (int)($decoded->data->user_id ?? 0);
			if ($user_id <= 0) throw new Exception("Invalid user_id");
		} catch (\Throwable $e) {
			return $this->response(['status'=>false,'message'=>'Invalid refresh token'], RestController::HTTP_UNAUTHORIZED);
		}

		// Pastikan refresh_token masih sama dengan session_id di DB (anti token reuse)
		$this->db->select('mstadmin.user_id,
						   mstadmin.username,
						   mstadmin.fullname AS fullname,
						   mstadmin.email,
						   mstadmin.level,
						   mstadmin.region_id AS region_id,
						   mstadmin.session_id,
						   refsales.management');
		$this->db->from('mstadmin');
		$this->db->join('refsales', 'mstadmin.user_id = refsales.user_id', 'left');
		$this->db->where('mstadmin.user_id', $user_id);
		$this->db->where('mstadmin.active', 'YES');
		$user = $this->db->get()->row();

		if (!$user) {
			return $this->response(['status'=>false,'message'=>'User not found'], RestController::HTTP_UNAUTHORIZED);
		}

		if ((string)$user->session_id !== (string)$refresh_token) {
			// refresh token sudah diganti/di-logout/diambil orang lain
			return $this->response(['status'=>false,'message'=>'Refresh token revoked'], RestController::HTTP_UNAUTHORIZED);
		}

		// === ROTATE refresh token (recommended) ===
		$payload_refresh = [
			"iss" => "gateway-fms",
			"iat" => time(),
			"exp" => time() + (7*24*3600),
			"data" => ["user_id" => (int)$user->user_id]
		];
		$new_refresh_token = JWT::encode($payload_refresh, $this->refresh_secret, 'HS256');

		// Access token baru (1 jam)
		$payload_access = [
			"iss" => "gateway-fms",
			"iat" => time(),
			"exp" => time() + 3600,
			"data" => [
				"user_id"    => (int)$user->user_id,
				"username"   => (string)$user->username,
				"fullname"   => (string)($user->fullname ?? ""),
				"email"      => (string)($user->email ?? ""),
				"level"      => (int)$user->level,
				"region_id"  => (string)$user->region_id,
				"management" => (int)($user->management ?? 0),
			]
		];
		$new_access_token = JWT::encode($payload_access, $this->secret_key, 'HS256');

		// Simpan refresh token baru (rotate)
		$this->db->update('mstadmin', ['session_id' => $new_refresh_token], ['user_id' => (int)$user->user_id]);

		return $this->response([
			'status' => true,
			'message' => 'Token refreshed',
			'access_token' => $new_access_token,
			'refresh_token' => $new_refresh_token,
			'expires_in' => 3600
		], RestController::HTTP_OK);
	}
	public function logout_post()
		{
			$apiKey = $this->input->get_request_header('x-api-key');
			if ($apiKey !== 'gateway-fms') {
				return $this->response(['status'=>false,'message'=>'Invalid API Key'], RestController::HTTP_UNAUTHORIZED);
			}

			$refresh_token = $this->post('refresh_token');
			if (!$refresh_token) {
				$json = json_decode($this->input->raw_input_stream, true);
				$refresh_token = is_array($json) ? ($json['refresh_token'] ?? '') : '';
			}
			$refresh_token = trim((string)$refresh_token);
			if ($refresh_token === '') {
				return $this->response(['status'=>false,'message'=>'Missing refresh_token'], 400);
			}

			// decode untuk ambil user_id (kalau invalid, tetap anggap logout sukses)
			try {
				$decoded = JWT::decode($refresh_token, new Key($this->refresh_secret, 'HS256'));
				$user_id = (int)($decoded->data->user_id ?? 0);
				if ($user_id > 0) {
					// clear hanya kalau match
					$this->db->where('user_id', $user_id);
					$this->db->where('session_id', $refresh_token);
					$this->db->update('mstadmin', ['session_id' => null]);
				}
			} catch (\Throwable $e) {
				// ignore
			}

			return $this->response(['status'=>true,'message'=>'Logged out'], 200);
		}


}