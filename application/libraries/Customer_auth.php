<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Shared authentication library for every customer-portal API controller.
 *
 * Install as: application/libraries/Customer_auth.php
 * Load with:  $this->load->library('Customer_auth');
 */
class Customer_auth
{
    /** @var CI_Controller */
    private $CI;

    /** @var CI_DB_query_builder */
    private $db;

    /** @var string */
    private $jwtKey = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->load->database('master', true);
        $this->jwtKey = trim((string) (
            getenv('GCL_JWT_KEY') ?: $this->CI->config->item('gcl_jwt_key')
        ));
    }

    /**
     * Validate a Bearer access token and return current user context.
     * This method never redirects and never clears a browser session.
     */
    public function authenticate(?string $requiredPermission = null): array
    {
        if ($this->jwtKey === '') {
            return $this->failed(500, 'Authentication service belum dikonfigurasi.');
        }

        $header = trim((string) $this->CI->input->get_request_header('Authorization'));
        if (stripos($header, 'Bearer ') !== 0) {
            return $this->failed(401, 'Missing or invalid Authorization header.');
        }

        try {
            $token = trim(substr($header, 7));
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));

            if (($decoded->type ?? '') !== 'access') {
                throw new RuntimeException('Invalid token type.');
            }

            $idUser = (int) ($decoded->data->id_user ?? 0);
            $tokenVersion = (int) ($decoded->data->token_version ?? -1);
            if ($idUser <= 0) {
                throw new RuntimeException('Invalid user.');
            }

            $user = $this->findActiveUser($idUser);
            if (!$user || (int) $user['token_version'] !== $tokenVersion) {
                throw new RuntimeException('Token revoked.');
            }

            $permissions = $this->getEffectivePermissions(
                (int) $user['id_user'],
                (int) $user['id_role']
            );

            if ($requiredPermission !== null &&
                !in_array($requiredPermission, $permissions, true)) {
                return $this->failed(403, 'Anda tidak memiliki hak akses untuk fitur ini.');
            }

            $shippers = $this->getAccessibleShippers((int) $user['id_user']);
            if (!$shippers) {
                throw new RuntimeException('No active shipper access.');
            }

            return [
                'ok' => true,
                'status' => 200,
                'user' => $user,
                'permissions' => $permissions,
                'shipper_access' => $shippers,
                'shipper_ids' => array_values(array_map(
                    static fn(array $row): int => (int) $row['id_shipper'],
                    $shippers
                )),
                'token_data' => (array) $decoded->data,
            ];
        } catch (Throwable $e) {
            log_message('error', 'Customer_auth rejected token: ' . $e->getMessage());
            return $this->failed(401, 'Invalid or expired access token.');
        }
    }

    /**
     * Resolve a requested shipper and ensure it belongs to the current user.
     */
    public function resolveShipperId(array $auth, int $requestedShipperId = 0): int
    {
        if (empty($auth['ok'])) {
            return 0;
        }

        $default = (int) ($auth['user']['id_shipper'] ?? 0);
        $candidate = $requestedShipperId > 0 ? $requestedShipperId : $default;

        foreach ($auth['shipper_access'] as $row) {
            if ((int) $row['id_shipper'] === $candidate) {
                return $candidate;
            }
        }

        foreach ($auth['shipper_access'] as $row) {
            if (!empty($row['is_default'])) {
                return (int) $row['id_shipper'];
            }
        }

        return 0;
    }

    private function findActiveUser(int $idUser): ?array
    {
        $row = $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
                'u.full_name', 'u.token_version', 'u.must_change_password',
                'r.role_code', 's.shipper_id AS shipper_code',
                's.shipper_name', 's.region_id',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role AND r.is_active = 1', 'inner')
            ->join('mstshipper s', "s.id_shipper = u.id_shipper AND s.isActive = 'Yes'", 'inner')
            ->where('u.id_user', $idUser)
            ->where('u.is_active', 1)
            ->where('u.deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    private function getEffectivePermissions(int $idUser, int $idRole): array
    {
        $result = [];

        $rolePermissions = $this->db
            ->select('p.permission_code')
            ->from('mstportal_role_permissions rp')
            ->join('mstportal_permissions p', 'p.id_permission = rp.id_permission AND p.is_active = 1', 'inner')
            ->where('rp.id_role', $idRole)
            ->get()
            ->result_array();

        foreach ($rolePermissions as $row) {
            $result[$row['permission_code']] = true;
        }

        $overrides = $this->db
            ->select('p.permission_code, o.access_type')
            ->from('mstshipper_user_permission_overrides o')
            ->join('mstportal_permissions p', 'p.id_permission = o.id_permission AND p.is_active = 1', 'inner')
            ->where('o.id_user', $idUser)
            ->get()
            ->result_array();

        foreach ($overrides as $row) {
            if ($row['access_type'] === 'ALLOW') {
                $result[$row['permission_code']] = true;
            } else {
                unset($result[$row['permission_code']]);
            }
        }

        $codes = array_keys($result);
        sort($codes);
        return $codes;
    }

    private function getAccessibleShippers(int $idUser): array
    {
        if (!$this->db->table_exists('mstshipper_user_access')) {
            $user = $this->findActiveUser($idUser);
            return $user ? [[
                'id_shipper' => (int) $user['id_shipper'],
                'shipper_code' => $user['shipper_code'],
                'shipper_name' => $user['shipper_name'],
                'region_id' => $user['region_id'],
                'is_default' => true,
                'role_code' => $user['role_code'],
            ]] : [];
        }

        $rows = $this->db
            ->select([
                'a.id_shipper', 'a.is_default',
                's.shipper_id AS shipper_code', 's.shipper_name', 's.region_id',
                'COALESCE(ar.role_code, ur.role_code) AS role_code',
            ])
            ->from('mstshipper_user_access a')
            ->join('mstshipper_users u', 'u.id_user = a.id_user', 'inner')
            ->join('mstshipper s', "s.id_shipper = a.id_shipper AND s.isActive = 'Yes'", 'inner')
            ->join('mstportal_roles ur', 'ur.id_role = u.id_role AND ur.is_active = 1', 'inner')
            ->join('mstportal_roles ar', 'ar.id_role = a.id_role AND ar.is_active = 1', 'left')
            ->where('a.id_user', $idUser)
            ->where('a.is_active', 1)
            ->where('u.is_active', 1)
            ->where('u.deleted_at IS NULL', null, false)
            ->order_by('a.is_default', 'DESC')
            ->order_by('a.id_shipper', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['id_shipper'] = (int) $row['id_shipper'];
            $row['is_default'] = (bool) $row['is_default'];
        }
        unset($row);

        return $rows;
    }

    private function failed(int $status, string $message): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
        ];
    }
}
