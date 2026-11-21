<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Admin extends MY_Controller
{
    /* Login dan refresh boleh tanpa access token. */
    protected $public_methods = ['login', 'refresh'];

    private $refresh_key = 'GANTI_DENGAN_REFRESH_KEY_ANDA_YANG_RUMIT';
    private $access_ttl = 3600;
    private $refresh_ttl = 604800; // 7 hari

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/Admin_model');
    }

    private function response_json($data, $status = 200)
    {
        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_status_header((int)$status)
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function valid_period_year($year)
    {
        return $this->is_valid_year($year);
    }

    private function build_access_payload($user, $period_year)
    {
        $issuedAt = time();

        return [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->access_ttl,
            'data' => [
                'user_id'   => (int)$user->user_id,
                'username'  => (string)$user->username,
                'level'     => $user->level,
                'region_id' => trim((string)$user->region_id),
                'year'      => (string)$period_year,
            ],
        ];
    }

    public function login()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        $username = isset($data->username)
            ? trim((string)$data->username)
            : trim((string)$this->input->post('username'));

        $password = isset($data->password)
            ? (string)$data->password
            : (string)$this->input->post('password');

        $period_year = isset($data->period_year)
            ? trim((string)$data->period_year)
            : trim((string)$this->input->post('period_year'));

        if ($username === '' || $password === '') {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'Username dan Password wajib diisi',
            ], 400);
        }

        if (!$this->valid_period_year($period_year)) {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'Periode tahun tidak valid',
            ], 400);
        }

        $user = $this->Admin_model->check_login($username, $password);

        if (!$user) {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'Username atau Password salah',
            ], 401);
        }

        $region_id = trim((string)($user->region_id ?? ''));

        if ($region_id === '') {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'User belum memiliki region',
            ], 403);
        }

        $issuedAt = time();

        $access_token = JWT::encode(
            $this->build_access_payload($user, $period_year),
            $this->secret_key,
            'HS256'
        );

        $refresh_payload = [
            'iat'     => $issuedAt,
            'exp'     => $issuedAt + $this->refresh_ttl,
            'user_id' => (int)$user->user_id,
            'year'    => (string)$period_year,
        ];

        $refresh_token = JWT::encode(
            $refresh_payload,
            $this->refresh_key,
            'HS256'
        );

        return $this->response_json([
            'status'        => 'success',
            'message'       => 'Login berhasil',
            'access_token'  => $access_token,
            'refresh_token' => $refresh_token,
        ]);
    }

    public function refresh()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        $refresh_token = isset($data->refresh_token)
            ? trim((string)$data->refresh_token)
            : trim((string)$this->input->post('refresh_token'));

        if ($refresh_token === '') {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'Refresh token tidak ditemukan',
            ], 400);
        }

        try {
            $decoded = JWT::decode(
                $refresh_token,
                new Key($this->refresh_key, 'HS256')
            );

            $user_id = (int)($decoded->user_id ?? 0);
            $period_year = trim((string)($decoded->year ?? ''));

            if ($user_id <= 0 || !$this->valid_period_year($period_year)) {
                return $this->response_json([
                    'status'  => 'error',
                    'message' => 'Refresh token tidak valid',
                ], 401);
            }

            /* Selalu ambil ulang user agar active/level/region terbaru terpakai. */
            $user = $this->Admin_model->get_user_by_id($user_id);

            if (!$user) {
                return $this->response_json([
                    'status'  => 'error',
                    'message' => 'User tidak ditemukan atau tidak aktif',
                ], 401);
            }

            $region_id = trim((string)($user->region_id ?? ''));

            if ($region_id === '') {
                return $this->response_json([
                    'status'  => 'error',
                    'message' => 'User tidak memiliki region',
                ], 403);
            }

            $new_access_token = JWT::encode(
                $this->build_access_payload($user, $period_year),
                $this->secret_key,
                'HS256'
            );

            return $this->response_json([
                'status'       => 'success',
                'access_token' => $new_access_token,
            ]);

        } catch (Exception $e) {
            return $this->response_json([
                'status'  => 'error',
                'message' => 'Refresh token tidak valid atau sudah kadaluarsa',
            ], 401);
        }
    }

    public function employees()
    {
        $region_id = $this->require_region();
        $q = trim((string)$this->input->get('q'));

        $employees = $this->Admin_model->get_employees($q, $region_id);

        return $this->response_json([
            'results' => $employees,
        ]);
    }
}
