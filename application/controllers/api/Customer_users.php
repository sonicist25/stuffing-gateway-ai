<?php
use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Customer_users extends RestController
{
    protected $methods = [
        'list_get'             => ['key' => false],
        'roles_get'            => ['key' => false],
        'create_post'          => ['key' => false],
        'update_post'          => ['key' => false],
        'status_post'          => ['key' => false],
        'reset_password_post'  => ['key' => false],
        'permissions_get'      => ['key' => false],
        'permissions_post'     => ['key' => false],
    ];

    /** @var CI_DB_query_builder */
    protected $db;

    private $jwt_key = '';

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, x-api-key');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        parent::__construct();

        $this->db = $this->load->database('master', true);
        $this->jwt_key = trim((string) (
            getenv('GCL_JWT_KEY') ?: $this->config->item('gcl_jwt_key')
        ));
    }

    /** GET /api/Customer_users/list */
    public function list_get()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $rows = $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.username', 'u.email',
                'u.full_name', 'u.phone', 'u.job_title',
                'u.is_primary', 'u.is_active', 'u.must_change_password',
                'u.email_verified_at', 'u.password_changed_at',
                'u.last_login_at', 'u.last_login_ip', 'u.locked_until',
                'u.created_at', 'u.updated_at',
                'r.id_role', 'r.role_code', 'r.role_name',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role', 'inner')
            ->where('u.id_shipper', (int) $auth['user']['id_shipper'])
            ->where('u.deleted_at IS NULL', null, false)
            ->order_by('u.is_primary', 'DESC')
            ->order_by('u.full_name', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['id_user'] = (int) $row['id_user'];
            $row['id_shipper'] = (int) $row['id_shipper'];
            $row['id_role'] = (int) $row['id_role'];
            $row['is_primary'] = (bool) $row['is_primary'];
            $row['is_active'] = (bool) $row['is_active'];
            $row['must_change_password'] = (bool) $row['must_change_password'];
            $row['is_current_user'] = ((int) $row['id_user'] === (int) $auth['user']['id_user']);
        }
        unset($row);

        $this->response([
            'status' => true,
            'data' => $rows,
        ], 200);
    }

    /** GET /api/Customer_users/roles */
    public function roles_get()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $this->db
            ->select('id_role, role_code, role_name, description')
            ->from('mstportal_roles')
            ->where('is_active', 1);

        // ADMIN boleh mengelola user, tetapi tidak boleh membuat OWNER baru.
        if ($auth['user']['role_code'] !== 'OWNER') {
            $this->db->where('role_code !=', 'OWNER');
        }

        $rows = $this->db
            ->order_by("FIELD(role_code, 'OWNER','ADMIN','OPERATIONS','FINANCE','VIEWER')", '', false)
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['id_role'] = (int) $row['id_role'];
        }
        unset($row);

        $this->response([
            'status' => true,
            'data' => $rows,
        ], 200);
    }

    /** POST /api/Customer_users/create */
    public function create_post()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? $password);
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $roleCode = strtoupper(trim((string) ($data['role_code'] ?? 'VIEWER')));
        $phone = trim((string) ($data['phone'] ?? ''));
        $jobTitle = trim((string) ($data['job_title'] ?? ''));
        $mustChangePassword = array_key_exists('must_change_password', $data)
            ? (int) ((bool) $data['must_change_password'])
            : 1;

        if ($username === '' || $fullName === '' || $password === '' || $roleCode === '') {
            $this->response([
                'status' => false,
                'message' => 'Username, nama user, role, dan password wajib diisi.',
            ], 422);
            return;
        }

        if (!preg_match('/^[a-z0-9._-]{3,100}$/', $username)) {
            $this->response([
                'status' => false,
                'message' => 'Username minimal 3 karakter dan hanya boleh huruf kecil, angka, titik, garis bawah, atau strip.',
            ], 422);
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response([
                'status' => false,
                'message' => 'Format email tidak valid.',
            ], 422);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->response([
                'status' => false,
                'message' => 'Konfirmasi password tidak sama.',
            ], 422);
            return;
        }

        if (!$this->isStrongPassword($password)) {
            $this->response([
                'status' => false,
                'message' => 'Password minimal 10 karakter dan harus memiliki huruf besar, huruf kecil, serta angka.',
            ], 422);
            return;
        }

        $role = $this->findAssignableRole($roleCode, $auth['user']['role_code']);
        if (!$role) {
            $this->response([
                'status' => false,
                'message' => 'Role tidak valid atau tidak boleh diberikan oleh user ini.',
            ], 403);
            return;
        }

        if ($this->usernameExists($username)) {
            $this->response([
                'status' => false,
                'message' => 'Username sudah digunakan.',
            ], 409);
            return;
        }

        if ($email !== '' && $this->emailExists($email)) {
            $this->response([
                'status' => false,
                'message' => 'Email sudah digunakan.',
            ], 409);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $inserted = $this->db->insert('mstshipper_users', [
            'id_shipper' => (int) $auth['user']['id_shipper'],
            'id_role' => (int) $role['id_role'],
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'phone' => $phone !== '' ? $phone : null,
            'job_title' => $jobTitle !== '' ? $jobTitle : null,
            'is_primary' => 0,
            'is_active' => 1,
            'must_change_password' => $mustChangePassword,
            'password_changed_at' => $mustChangePassword ? null : $now,
            'created_by_user_id' => (int) $auth['user']['id_user'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!$inserted) {
            log_message('error', 'Create customer user DB error: ' . json_encode($this->db->error()));
            $this->response([
                'status' => false,
                'message' => 'Gagal membuat user baru.',
            ], 500);
            return;
        }

        $newUserId = (int) $this->db->insert_id();
        $this->response([
            'status' => true,
            'message' => 'User berhasil dibuat.',
            'data' => $this->getManagedUser($newUserId, (int) $auth['user']['id_shipper']),
        ], 201);
    }

    /** POST /api/Customer_users/update */
    public function update_post()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $target = $this->getManagedUser($userId, (int) $auth['user']['id_shipper']);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }

        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mengubah akun OWNER.'], 403);
            return;
        }

        $update = [];

        if (array_key_exists('full_name', $data)) {
            $fullName = trim((string) $data['full_name']);
            if ($fullName === '') {
                $this->response(['status' => false, 'message' => 'Nama user tidak boleh kosong.'], 422);
                return;
            }
            $update['full_name'] = $fullName;
        }

        if (array_key_exists('email', $data)) {
            $email = strtolower(trim((string) $data['email']));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response(['status' => false, 'message' => 'Format email tidak valid.'], 422);
                return;
            }
            if ($email !== '' && $this->emailExists($email, $userId)) {
                $this->response(['status' => false, 'message' => 'Email sudah digunakan.'], 409);
                return;
            }
            $update['email'] = $email !== '' ? $email : null;
        }

        if (array_key_exists('username', $data)) {
            $username = strtolower(trim((string) $data['username']));
            if (!preg_match('/^[a-z0-9._-]{3,100}$/', $username)) {
                $this->response([
                    'status' => false,
                    'message' => 'Username minimal 3 karakter dan hanya boleh huruf kecil, angka, titik, garis bawah, atau strip.',
                ], 422);
                return;
            }
            if ($this->usernameExists($username, $userId)) {
                $this->response(['status' => false, 'message' => 'Username sudah digunakan.'], 409);
                return;
            }
            $update['username'] = $username;
        }

        if (array_key_exists('phone', $data)) {
            $phone = trim((string) $data['phone']);
            $update['phone'] = $phone !== '' ? $phone : null;
        }

        if (array_key_exists('job_title', $data)) {
            $jobTitle = trim((string) $data['job_title']);
            $update['job_title'] = $jobTitle !== '' ? $jobTitle : null;
        }

        if (array_key_exists('role_code', $data)) {
            $roleCode = strtoupper(trim((string) $data['role_code']));
            $role = $this->findAssignableRole($roleCode, $auth['user']['role_code']);
            if (!$role) {
                $this->response([
                    'status' => false,
                    'message' => 'Role tidak valid atau tidak boleh diberikan oleh user ini.',
                ], 403);
                return;
            }

            if ((bool) $target['is_primary'] && $roleCode !== 'OWNER') {
                $this->response([
                    'status' => false,
                    'message' => 'Akun primary harus tetap menggunakan role OWNER.',
                ], 422);
                return;
            }

            $update['id_role'] = (int) $role['id_role'];
            $update['token_version'] = (int) $target['token_version'] + 1;
        }

        if (!$update) {
            $this->response(['status' => false, 'message' => 'Tidak ada data yang diperbarui.'], 422);
            return;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');
        $updated = $this->db->where('id_user', $userId)->update('mstshipper_users', $update);

        if (!$updated) {
            $this->response(['status' => false, 'message' => 'Gagal memperbarui user.'], 500);
            return;
        }

        $this->response([
            'status' => true,
            'message' => 'User berhasil diperbarui.',
            'data' => $this->getManagedUser($userId, (int) $auth['user']['id_shipper']),
        ], 200);
    }

    /** POST /api/Customer_users/status */
    public function status_post()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $isActive = (int) ((bool) ($data['is_active'] ?? false));
        $target = $this->getManagedUser($userId, (int) $auth['user']['id_shipper']);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }

        if ($userId === (int) $auth['user']['id_user'] && $isActive === 0) {
            $this->response(['status' => false, 'message' => 'Anda tidak dapat menonaktifkan akun sendiri.'], 422);
            return;
        }

        if ((bool) $target['is_primary'] && $isActive === 0) {
            $this->response(['status' => false, 'message' => 'Akun primary tidak dapat dinonaktifkan.'], 422);
            return;
        }

        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mengubah status OWNER.'], 403);
            return;
        }

        $updated = $this->db->where('id_user', $userId)->update('mstshipper_users', [
            'is_active' => $isActive,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => (int) $target['token_version'] + 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            $this->response(['status' => false, 'message' => 'Gagal memperbarui status user.'], 500);
            return;
        }

        $this->response([
            'status' => true,
            'message' => $isActive ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
            'data' => $this->getManagedUser($userId, (int) $auth['user']['id_shipper']),
        ], 200);
    }

    /** POST /api/Customer_users/reset_password */
    public function reset_password_post()
    {
        $auth = $this->authenticate('users.manage');
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $newPassword = (string) ($data['new_password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');
        $target = $this->getManagedUser($userId, (int) $auth['user']['id_shipper']);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }

        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mereset password OWNER.'], 403);
            return;
        }

        if ($newPassword === '' || $confirmPassword === '') {
            $this->response(['status' => false, 'message' => 'Password baru dan konfirmasinya wajib diisi.'], 422);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->response(['status' => false, 'message' => 'Konfirmasi password tidak sama.'], 422);
            return;
        }

        if (!$this->isStrongPassword($newPassword)) {
            $this->response([
                'status' => false,
                'message' => 'Password minimal 10 karakter dan harus memiliki huruf besar, huruf kecil, serta angka.',
            ], 422);
            return;
        }

        $updated = $this->db->where('id_user', $userId)->update('mstshipper_users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'password_changed_at' => null,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => (int) $target['token_version'] + 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            $this->response(['status' => false, 'message' => 'Gagal mereset password.'], 500);
            return;
        }

        $this->response([
            'status' => true,
            'message' => 'Password sementara berhasil dibuat. User wajib menggantinya saat login.',
            'must_change_password' => true,
        ], 200);
    }

    /** GET /api/Customer_users/permissions?id_user=123 */
    public function permissions_get()
    {
        $auth = $this->authenticate('permissions.manage');
        if (!$auth) {
            return;
        }

        $userId = (int) $this->get('id_user');
        $target = $this->getManagedUser($userId, (int) $auth['user']['id_shipper']);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }

        $rows = $this->db
            ->select([
                'p.id_permission', 'p.permission_code', 'p.permission_name',
                'p.module_name', 'p.description',
                'CASE WHEN rp.id_permission IS NULL THEN 0 ELSE 1 END AS role_granted',
                'o.access_type',
            ], false)
            ->from('mstportal_permissions p')
            ->join(
                'mstportal_role_permissions rp',
                'rp.id_permission = p.id_permission AND rp.id_role = ' . (int) $target['id_role'],
                'left'
            )
            ->join(
                'mstshipper_user_permission_overrides o',
                'o.id_permission = p.id_permission AND o.id_user = ' . (int) $target['id_user'],
                'left'
            )
            ->where('p.is_active', 1)
            ->order_by('p.module_name', 'ASC')
            ->order_by('p.permission_code', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $roleGranted = (bool) $row['role_granted'];
            $accessType = $row['access_type'];
            $effective = $accessType === 'ALLOW'
                ? true
                : ($accessType === 'DENY' ? false : $roleGranted);

            $row['id_permission'] = (int) $row['id_permission'];
            $row['role_granted'] = $roleGranted;
            $row['effective_access'] = $effective;
            $row['override'] = $accessType ?: 'DEFAULT';
            unset($row['access_type']);
        }
        unset($row);

        $this->response([
            'status' => true,
            'user' => $target,
            'data' => $rows,
        ], 200);
    }

    /** POST /api/Customer_users/permissions */
    public function permissions_post()
    {
        $auth = $this->authenticate('permissions.manage');
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $overrides = $data['overrides'] ?? [];
        $target = $this->getManagedUser($userId, (int) $auth['user']['id_shipper']);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }

        if (!is_array($overrides)) {
            $this->response(['status' => false, 'message' => 'Overrides harus berupa array.'], 422);
            return;
        }

        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mengubah permission OWNER.'], 403);
            return;
        }

        $permissionRows = $this->db
            ->select('id_permission, permission_code')
            ->from('mstportal_permissions')
            ->where('is_active', 1)
            ->get()
            ->result_array();

        $permissionMap = [];
        foreach ($permissionRows as $permission) {
            $permissionMap[$permission['permission_code']] = (int) $permission['id_permission'];
        }

        $this->db->trans_begin();

        foreach ($overrides as $override) {
            $permissionCode = trim((string) ($override['permission_code'] ?? ''));
            $accessType = strtoupper(trim((string) ($override['access_type'] ?? 'DEFAULT')));

            if (!isset($permissionMap[$permissionCode])) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => false,
                    'message' => 'Permission tidak dikenal: ' . $permissionCode,
                ], 422);
                return;
            }

            $permissionId = $permissionMap[$permissionCode];

            if ($accessType === 'DEFAULT') {
                $this->db
                    ->where('id_user', $userId)
                    ->where('id_permission', $permissionId)
                    ->delete('mstshipper_user_permission_overrides');
                continue;
            }

            if (!in_array($accessType, ['ALLOW', 'DENY'], true)) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => false,
                    'message' => 'access_type harus DEFAULT, ALLOW, atau DENY.',
                ], 422);
                return;
            }

            $sql = "INSERT INTO mstshipper_user_permission_overrides
                    (id_user, id_permission, access_type, created_by_user_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                      access_type = VALUES(access_type),
                      created_by_user_id = VALUES(created_by_user_id),
                      updated_at = NOW()";

            $this->db->query($sql, [
                $userId,
                $permissionId,
                $accessType,
                (int) $auth['user']['id_user'],
            ]);
        }

        // Permission berubah: cabut token target supaya token baru memuat permission terbaru.
        $this->db->set('token_version', 'token_version + 1', false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('id_user', $userId)
            ->update('mstshipper_users');

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal menyimpan permission.'], 500);
            return;
        }

        $this->db->trans_commit();
        $this->response([
            'status' => true,
            'message' => 'Permission user berhasil diperbarui. User perlu login ulang.',
        ], 200);
    }

    private function authenticate($requiredPermission)
    {
        if ($this->jwt_key === '') {
            $this->response([
                'status' => false,
                'message' => 'JWT configuration belum tersedia.',
            ], 500);
            return null;
        }

        $authHeader = trim((string) $this->input->get_request_header('Authorization'));
        if (stripos($authHeader, 'Bearer ') !== 0) {
            $this->response([
                'status' => false,
                'message' => 'Missing or invalid Authorization header.',
            ], 401);
            return null;
        }

        try {
            $decoded = JWT::decode(substr($authHeader, 7), new Key($this->jwt_key, 'HS256'));
            if (($decoded->type ?? '') !== 'access') {
                throw new RuntimeException('Invalid token type.');
            }

            $userId = (int) ($decoded->data->id_user ?? 0);
            $tokenVersion = (int) ($decoded->data->token_version ?? 0);
            $user = $this->findAuthenticatedUser($userId);

            if (!$user ||
                (int) $user['is_active'] !== 1 ||
                $user['shipper_active'] !== 'Yes' ||
                (int) $user['token_version'] !== $tokenVersion) {
                throw new RuntimeException('Token revoked.');
            }

            if ((int) $user['must_change_password'] === 1) {
                $this->response([
                    'status' => false,
                    'message' => 'Password sementara wajib diganti terlebih dahulu.',
                    'must_change_password' => true,
                ], 403);
                return null;
            }

            $permissions = $this->getEffectivePermissions((int) $user['id_user'], (int) $user['id_role']);
            if (!in_array($requiredPermission, $permissions, true)) {
                $this->response([
                    'status' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
                return null;
            }

            return [
                'user' => $user,
                'permissions' => $permissions,
            ];
        } catch (Throwable $e) {
            $this->response([
                'status' => false,
                'message' => 'Invalid or expired access token.',
            ], 401);
            return null;
        }
    }

    private function findAuthenticatedUser($userId)
    {
        return $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username',
                'u.full_name', 'u.is_active', 'u.must_change_password', 'u.token_version',
                'r.role_code', 'r.role_name',
                's.shipper_id AS shipper_code', 's.shipper_name',
                's.region_id', 's.isActive AS shipper_active',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role AND r.is_active = 1', 'inner')
            ->join('mstshipper s', 's.id_shipper = u.id_shipper', 'inner')
            ->where('u.id_user', (int) $userId)
            ->where('u.deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }

    private function getManagedUser($userId, $shipperId)
    {
        $row = $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
                'u.full_name', 'u.phone', 'u.job_title', 'u.is_primary',
                'u.is_active', 'u.must_change_password', 'u.token_version',
                'u.password_changed_at', 'u.last_login_at', 'u.last_login_ip',
                'u.locked_until', 'u.created_at', 'u.updated_at',
                'r.role_code', 'r.role_name',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role', 'inner')
            ->where('u.id_user', (int) $userId)
            ->where('u.id_shipper', (int) $shipperId)
            ->where('u.deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            return null;
        }

        $row['id_user'] = (int) $row['id_user'];
        $row['id_shipper'] = (int) $row['id_shipper'];
        $row['id_role'] = (int) $row['id_role'];
        $row['is_primary'] = (bool) $row['is_primary'];
        $row['is_active'] = (bool) $row['is_active'];
        $row['must_change_password'] = (bool) $row['must_change_password'];
        $row['token_version'] = (int) $row['token_version'];
        return $row;
    }

    private function findAssignableRole($roleCode, $currentRoleCode)
    {
        if ($roleCode === 'OWNER' && $currentRoleCode !== 'OWNER') {
            return null;
        }

        return $this->db
            ->select('id_role, role_code, role_name')
            ->from('mstportal_roles')
            ->where('role_code', $roleCode)
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }

    private function usernameExists($username, $ignoreUserId = 0)
    {
        $this->db->from('mstshipper_users')->where('username', $username);
        if ($ignoreUserId > 0) {
            $this->db->where('id_user !=', $ignoreUserId);
        }
        return $this->db->count_all_results() > 0;
    }

    private function emailExists($email, $ignoreUserId = 0)
    {
        $this->db->from('mstshipper_users')->where('email', $email);
        if ($ignoreUserId > 0) {
            $this->db->where('id_user !=', $ignoreUserId);
        }
        return $this->db->count_all_results() > 0;
    }

    private function getEffectivePermissions($userId, $roleId)
    {
        $permissions = [];

        $rows = $this->db
            ->select('p.permission_code')
            ->from('mstportal_role_permissions rp')
            ->join('mstportal_permissions p', 'p.id_permission = rp.id_permission AND p.is_active = 1', 'inner')
            ->where('rp.id_role', $roleId)
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $permissions[$row['permission_code']] = true;
        }

        $overrides = $this->db
            ->select('p.permission_code, o.access_type')
            ->from('mstshipper_user_permission_overrides o')
            ->join('mstportal_permissions p', 'p.id_permission = o.id_permission AND p.is_active = 1', 'inner')
            ->where('o.id_user', $userId)
            ->get()
            ->result_array();

        foreach ($overrides as $override) {
            if ($override['access_type'] === 'ALLOW') {
                $permissions[$override['permission_code']] = true;
            } else {
                unset($permissions[$override['permission_code']]);
            }
        }

        $codes = array_keys($permissions);
        sort($codes);
        return $codes;
    }

    private function jsonInput()
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isStrongPassword($password)
    {
        return strlen($password) >= 10
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password);
    }
}
