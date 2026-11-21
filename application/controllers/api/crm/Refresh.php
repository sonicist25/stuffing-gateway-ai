<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Refresh extends RestController {

    private $secret_key = "your-secret-key";
    private $refresh_secret = "your-refresh-secret";

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index_post() {
        $refresh_token = $this->post('refresh_token');
        if (!$refresh_token) {
            return $this->response(['status'=>false,'message'=>'Refresh token wajib'], RestController::HTTP_BAD_REQUEST);
        }

        try {
            $decoded = JWT::decode($refresh_token, new Key($this->refresh_secret, 'HS256'));
            $user_id = $decoded->data->user_id;

            $user = $this->db->get_where('mstadmin',['user_id'=>$user_id,'active'=>'YES'])->row();
            if (!$user) return $this->response(['status'=>false,'message'=>'User tidak ditemukan'], RestController::HTTP_NOT_FOUND);

            // Buat access token baru
            $payload_access = [
                "iss"=>"gateway-fms",
                "iat"=>time(),
                "exp"=>time()+3600,
                "data"=>[
                    "user_id"=>$user->user_id,
                    "username"=>$user->username,
                    "level"=>$user->level
                ]
            ];
            $new_access_token = JWT::encode($payload_access, $this->secret_key, 'HS256');

            return $this->response([
                'status'=>true,
                'message'=>'Access token diperbarui',
                'access_token'=>$new_access_token
            ], RestController::HTTP_OK);

        } catch (Exception $e) {
            return $this->response(['status'=>false,'message'=>'Refresh token tidak valid: '.$e->getMessage()], RestController::HTTP_UNAUTHORIZED);
        }
    }
}