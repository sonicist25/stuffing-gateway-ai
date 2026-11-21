<?php
use chriskacerguis\RestServer\RestController;

/**
 * Customer portal profile and company-user management.
 *
 * Install as:
 *   application/controllers/api/Customer_profile.php
 *
 * Requires:
 *   application/libraries/Customer_auth.php
 *   mstshipper_users / mstportal_roles / mstshipper_user_access tables
 */
class Customer_profile extends RestController
{
    protected $methods = [
        'overview_get'          => ['key' => false],
        'create_user_post'      => ['key' => false],
        'update_user_post'      => ['key' => false],
        'toggle_user_post'      => ['key' => false],
        'reset_password_post'   => ['key' => false],
        'delete_user_post'      => ['key' => false],
    ];

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, x-api-key');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        parent::__construct();
        $this->db = $this->load->database('master', true);
        $this->load->library('Customer_auth');
    }

    /** GET /api/Customer_profile/overview */
    public function overview_get()
    {
        $auth = $this->customer_auth->authenticate();
        if (!$this->sendAuthFailure($auth)) {
            return;
        }

        $shipperIds = array_values(array_unique(array_map('intval', $auth['shipper_ids'])));
        $canManageUsers = in_array('users.manage', $auth['permissions'], true);

        $response = [
            'status' => true,
            'data' => [
                'current_user' => $this->buildCurrentUser($auth),
                'shippers' => $this->getShipperDetails($shipperIds, $auth['shipper_access']),
                'can_manage_users' => $canManageUsers,
                'users' => [],
                'roles' => [],
            ],
        ];

        if ($canManageUsers) {
            $response['data']['users'] = $this->listManageableUsers($auth);
            $response['data']['roles'] = $this->getAssignableRoles($auth['user']['role_code']);
        }

        $this->response($response, 200);
    }

    /** POST /api/Customer_profile/create_user */
    public function create_user_post()
    {
        $auth = $this->requireUserManager();
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $jobTitle = trim((string) ($data['job_title'] ?? ''));
        $roleCode = strtoupper(trim((string) ($data['role_code'] ?? 'VIEWER')));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        $validationError = $this->validateUserIdentity(
            $username,
            $email,
            $fullName,
            $password,
            $confirmPassword,
            true
        );
        if ($validationError !== null) {
            $this->response(['status' => false, 'message' => $validationError], 422);
            return;
        }

        if ($roleCode === 'OWNER') {
            $this->response([
                'status' => false,
                'message' => 'User anak tidak boleh dibuat sebagai OWNER. Gunakan role ADMIN atau role lain.',
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
            $this->response(['status' => false, 'message' => 'Username sudah digunakan.'], 409);
            return;
        }
        if ($email !== '' && $this->emailExists($email)) {
            $this->response(['status' => false, 'message' => 'Email sudah digunakan.'], 409);
            return;
        }

        $selection = $this->validateShipperSelection($auth, $data);
        if (!$selection['ok']) {
            $this->response(['status' => false, 'message' => $selection['message']], 422);
            return;
        }

        $mustChangePassword = array_key_exists('must_change_password', $data)
            ? (int) ((bool) $data['must_change_password'])
            : 1;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_begin();

        $this->db->insert('mstshipper_users', [
            'id_shipper' => $selection['default_shipper_id'],
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

        $newUserId = (int) $this->db->insert_id();
        if ($newUserId <= 0) {
            $this->db->trans_rollback();
            log_message('error', 'Customer profile create user error: ' . json_encode($this->db->error()));
            $this->response(['status' => false, 'message' => 'Gagal membuat user baru.'], 500);
            return;
        }

        if (!$this->replaceUserShipperAccess(
            $newUserId,
            $selection['shipper_ids'],
            $selection['default_shipper_id'],
            (int) $role['id_role'],
            (int) $auth['user']['id_user']
        )) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal menyimpan akses shipper user.'], 500);
            return;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal membuat user baru.'], 500);
            return;
        }

        $this->db->trans_commit();
        $this->response([
            'status' => true,
            'message' => 'User berhasil ditambahkan.',
            'data' => $this->findManageableUser($newUserId, $auth),
        ], 201);
    }

    /** POST /api/Customer_profile/update_user */
    public function update_user_post()
    {
        $auth = $this->requireUserManager();
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $target = $this->findManageableUser($userId, $auth);
        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan atau berada di luar akses Anda.'], 404);
            return;
        }

        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mengubah akun OWNER.'], 403);
            return;
        }

        $username = strtolower(trim((string) ($data['username'] ?? $target['username'])));
        $email = strtolower(trim((string) ($data['email'] ?? ($target['email'] ?? ''))));
        $fullName = trim((string) ($data['full_name'] ?? $target['full_name']));
        $validationError = $this->validateUserIdentity($username, $email, $fullName, '', '', false);
        if ($validationError !== null) {
            $this->response(['status' => false, 'message' => $validationError], 422);
            return;
        }
        if ($this->usernameExists($username, $userId)) {
            $this->response(['status' => false, 'message' => 'Username sudah digunakan.'], 409);
            return;
        }
        if ($email !== '' && $this->emailExists($email, $userId)) {
            $this->response(['status' => false, 'message' => 'Email sudah digunakan.'], 409);
            return;
        }

        $roleCode = strtoupper(trim((string) ($data['role_code'] ?? $target['role_code'])));
        $role = $this->findAssignableRole($roleCode, $auth['user']['role_code']);
        if (!$role) {
            $this->response(['status' => false, 'message' => 'Role tidak valid atau tidak boleh diberikan.'], 403);
            return;
        }
        if ((bool) $target['is_primary'] && $roleCode !== 'OWNER') {
            $this->response(['status' => false, 'message' => 'Akun primary harus tetap OWNER.'], 422);
            return;
        }

        $selection = $this->validateShipperSelection($auth, $data, $target);
        if (!$selection['ok']) {
            $this->response(['status' => false, 'message' => $selection['message']], 422);
            return;
        }

        $changesSensitive = (int) $target['id_role'] !== (int) $role['id_role']
            || (int) $target['id_shipper'] !== (int) $selection['default_shipper_id']
            || $this->normalizedIds($target['shipper_ids']) !== $this->normalizedIds($selection['shipper_ids']);

        if ($userId === (int) $auth['user']['id_user'] && $changesSensitive) {
            $this->response([
                'status' => false,
                'message' => 'Role dan akses shipper akun yang sedang digunakan tidak dapat diubah dari halaman ini.',
            ], 422);
            return;
        }

        $update = [
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'full_name' => $fullName,
            'phone' => ($phone = trim((string) ($data['phone'] ?? ($target['phone'] ?? '')))) !== '' ? $phone : null,
            'job_title' => ($jobTitle = trim((string) ($data['job_title'] ?? ($target['job_title'] ?? '')))) !== '' ? $jobTitle : null,
            'id_role' => (int) $role['id_role'],
            'id_shipper' => (int) $selection['default_shipper_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($changesSensitive) {
            $update['token_version'] = (int) $target['token_version'] + 1;
        }

        $this->db->trans_begin();
        $this->db->where('id_user', $userId)->update('mstshipper_users', $update);

        if (!$this->replaceUserShipperAccess(
            $userId,
            $selection['shipper_ids'],
            $selection['default_shipper_id'],
            (int) $role['id_role'],
            (int) $auth['user']['id_user']
        )) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal memperbarui akses shipper user.'], 500);
            return;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal memperbarui user.'], 500);
            return;
        }

        $this->db->trans_commit();
        $this->response([
            'status' => true,
            'message' => $changesSensitive
                ? 'User berhasil diperbarui. User tersebut perlu login ulang.'
                : 'User berhasil diperbarui.',
            'data' => $this->findManageableUser($userId, $auth),
        ], 200);
    }

    /** POST /api/Customer_profile/toggle_user */
    public function toggle_user_post()
    {
        $auth = $this->requireUserManager();
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $isActive = (int) ((bool) ($data['is_active'] ?? false));
        $target = $this->findManageableUser($userId, $auth);

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

        $this->db->trans_begin();
        $this->db->where('id_user', $userId)->update('mstshipper_users', [
            'is_active' => $isActive,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => (int) $target['token_version'] + 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->table_exists('mstshipper_user_access')) {
            $this->db->where('id_user', $userId)->update('mstshipper_user_access', [
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal memperbarui status user.'], 500);
            return;
        }
        $this->db->trans_commit();

        $this->response([
            'status' => true,
            'message' => $isActive ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
            'data' => $this->findManageableUser($userId, $auth),
        ], 200);
    }

    /** POST /api/Customer_profile/reset_password */
    public function reset_password_post()
    {
        $auth = $this->requireUserManager();
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $password = (string) ($data['new_password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');
        $target = $this->findManageableUser($userId, $auth);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }
        if ($auth['user']['role_code'] !== 'OWNER' && $target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'ADMIN tidak boleh mereset password OWNER.'], 403);
            return;
        }
        if ($password !== $confirmPassword) {
            $this->response(['status' => false, 'message' => 'Konfirmasi password tidak sama.'], 422);
            return;
        }
        if (!$this->isStrongPassword($password)) {
            $this->response([
                'status' => false,
                'message' => 'Password minimal 10 karakter, dengan huruf besar, huruf kecil, dan angka.',
            ], 422);
            return;
        }

        $this->db->where('id_user', $userId)->update('mstshipper_users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'password_changed_at' => null,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => (int) $target['token_version'] + 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->response([
            'status' => true,
            'message' => 'Password sementara berhasil dibuat. User harus menggantinya saat login berikutnya.',
        ], 200);
    }

    /** POST /api/Customer_profile/delete_user */
    public function delete_user_post()
    {
        $auth = $this->requireUserManager();
        if (!$auth) {
            return;
        }

        $data = $this->jsonInput();
        $userId = (int) ($data['id_user'] ?? 0);
        $target = $this->findManageableUser($userId, $auth);

        if (!$target) {
            $this->response(['status' => false, 'message' => 'User tidak ditemukan.'], 404);
            return;
        }
        if ($auth['user']['role_code'] !== 'OWNER') {
            $this->response(['status' => false, 'message' => 'Hanya OWNER yang dapat menghapus user anak.'], 403);
            return;
        }
        if ($userId === (int) $auth['user']['id_user']) {
            $this->response(['status' => false, 'message' => 'Anda tidak dapat menghapus akun sendiri.'], 422);
            return;
        }
        if ((bool) $target['is_primary']) {
            $this->response(['status' => false, 'message' => 'Akun primary tidak dapat dihapus.'], 422);
            return;
        }
        if ($target['role_code'] === 'OWNER') {
            $this->response(['status' => false, 'message' => 'Akun OWNER tidak dapat dihapus sebagai user anak.'], 422);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $this->db->where('id_user', $userId)->update('mstshipper_users', [
            'is_active' => 0,
            'deleted_at' => $now,
            'token_version' => (int) $target['token_version'] + 1,
            'updated_at' => $now,
        ]);
        if ($this->db->table_exists('mstshipper_user_access')) {
            $this->db->where('id_user', $userId)->update('mstshipper_user_access', [
                'is_active' => 0,
                'updated_at' => $now,
            ]);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal menghapus user.'], 500);
            return;
        }
        $this->db->trans_commit();

        $this->response([
            'status' => true,
            'message' => 'User berhasil dihapus. Riwayat user tetap disimpan untuk audit.',
        ], 200);
    }

    private function requireUserManager(): ?array
    {
        $auth = $this->customer_auth->authenticate('users.manage');
        if (!$this->sendAuthFailure($auth)) {
            return null;
        }
        return $auth;
    }

    private function sendAuthFailure(array $auth): bool
    {
        if (!empty($auth['ok'])) {
            return true;
        }
        $this->response([
            'status' => false,
            'message' => $auth['message'] ?? 'Unauthorized.',
        ], (int) ($auth['status'] ?? 401));
        return false;
    }

    private function buildCurrentUser(array $auth): array
    {
        $detail = $this->db
            ->select('email, phone, job_title, is_primary, last_login_at, password_changed_at, created_at')
            ->from('mstshipper_users')
            ->where('id_user', (int) $auth['user']['id_user'])
            ->limit(1)
            ->get()
            ->row_array() ?: [];

        return [
            'id_user' => (int) $auth['user']['id_user'],
            'id_shipper' => (int) $auth['user']['id_shipper'],
            'username' => $auth['user']['username'],
            'email' => $detail['email'] ?? $auth['user']['email'],
            'full_name' => $auth['user']['full_name'],
            'phone' => $detail['phone'] ?? null,
            'job_title' => $detail['job_title'] ?? null,
            'is_primary' => (bool) ($detail['is_primary'] ?? false),
            'last_login_at' => $detail['last_login_at'] ?? null,
            'password_changed_at' => $detail['password_changed_at'] ?? null,
            'created_at' => $detail['created_at'] ?? null,
            'role_code' => $auth['user']['role_code'],
            'must_change_password' => (bool) $auth['user']['must_change_password'],
            'permissions' => array_values($auth['permissions']),
            'shipper_ids' => array_values(array_map('intval', $auth['shipper_ids'])),
        ];
    }

    private function getShipperDetails(array $shipperIds, array $accessRows): array
    {
        if (!$shipperIds) {
            return [];
        }

        $accessMap = [];
        foreach ($accessRows as $row) {
            $accessMap[(int) $row['id_shipper']] = $row;
        }

        $rows = $this->db
            ->select([
                'id_shipper', 'shipper_id AS shipper_code', 'shipper_name',
                'shipper_address', 'shipper_npwp', 'npwp_name', 'npwp_address',
                'nib', 'cp_name', 'cp_email', 'cp_phone', 'isActive', 'region_id',
                'sales', 'account', 'payment', 'type',
            ])
            ->from('mstshipper')
            ->where_in('id_shipper', $shipperIds)
            ->order_by('shipper_name', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $id = (int) $row['id_shipper'];
            $access = $accessMap[$id] ?? [];
            $row['id_shipper'] = $id;
            $row['is_default'] = (bool) ($access['is_default'] ?? false);
            $row['access_role_code'] = $access['role_code'] ?? null;
            $row['is_active'] = $row['isActive'] === 'Yes';
            unset($row['isActive']);
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            if ($a['is_default'] !== $b['is_default']) {
                return $a['is_default'] ? -1 : 1;
            }
            return strcasecmp((string) $a['shipper_name'], (string) $b['shipper_name']);
        });
        return $rows;
    }

    private function listManageableUsers(array $auth): array
    {
        $allowedIds = array_values(array_unique(array_map('intval', $auth['shipper_ids'])));
        if (!$allowedIds) {
            return [];
        }

        $escapedIds = implode(',', $allowedIds);
        $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
                'u.full_name', 'u.phone', 'u.job_title', 'u.is_primary', 'u.is_active',
                'u.must_change_password', 'u.token_version', 'u.last_login_at',
                'u.password_changed_at', 'u.locked_until', 'u.created_at', 'u.updated_at',
                'r.role_code', 'r.role_name',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role', 'inner')
            ->where('u.deleted_at IS NULL', null, false);

        if ($this->db->table_exists('mstshipper_user_access')) {
            $this->db->where("(u.id_shipper IN ({$escapedIds}) OR EXISTS (
                SELECT 1 FROM mstshipper_user_access ax
                WHERE ax.id_user = u.id_user
                  AND ax.id_shipper IN ({$escapedIds})
            ))", null, false);
        } else {
            $this->db->where_in('u.id_shipper', $allowedIds);
        }

        $rows = $this->db
            ->order_by('u.is_primary', 'DESC')
            ->order_by('u.full_name', 'ASC')
            ->get()
            ->result_array();

        $result = [];
        foreach ($rows as $row) {
            $accesses = $this->getUserAccessRows((int) $row['id_user']);
            $targetIds = $accesses
                ? array_map(static fn(array $x): int => (int) $x['id_shipper'], $accesses)
                : [(int) $row['id_shipper']];

            if (array_diff($targetIds, $allowedIds)) {
                continue;
            }
            $result[] = $this->castUserRow($row, $accesses, (int) $auth['user']['id_user']);
        }
        return $result;
    }

    private function findManageableUser(int $userId, array $auth): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $row = $this->db
            ->select([
                'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
                'u.full_name', 'u.phone', 'u.job_title', 'u.is_primary', 'u.is_active',
                'u.must_change_password', 'u.token_version', 'u.last_login_at',
                'u.password_changed_at', 'u.locked_until', 'u.created_at', 'u.updated_at',
                'r.role_code', 'r.role_name',
            ])
            ->from('mstshipper_users u')
            ->join('mstportal_roles r', 'r.id_role = u.id_role', 'inner')
            ->where('u.id_user', $userId)
            ->where('u.deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            return null;
        }

        $accesses = $this->getUserAccessRows($userId);
        $targetIds = $accesses
            ? array_map(static fn(array $x): int => (int) $x['id_shipper'], $accesses)
            : [(int) $row['id_shipper']];
        $allowedIds = array_values(array_unique(array_map('intval', $auth['shipper_ids'])));

        if (!$targetIds || array_diff($targetIds, $allowedIds)) {
            return null;
        }

        return $this->castUserRow($row, $accesses, (int) $auth['user']['id_user']);
    }

    private function getUserAccessRows(int $userId): array
    {
        if (!$this->db->table_exists('mstshipper_user_access')) {
            return [];
        }

        $rows = $this->db
            ->select([
                'a.id_shipper', 'a.is_default', 'a.is_active',
                's.shipper_id AS shipper_code', 's.shipper_name',
                'COALESCE(ar.role_code, ur.role_code) AS role_code',
            ])
            ->from('mstshipper_user_access a')
            ->join('mstshipper_users u', 'u.id_user = a.id_user', 'inner')
            ->join('mstshipper s', 's.id_shipper = a.id_shipper', 'inner')
            ->join('mstportal_roles ur', 'ur.id_role = u.id_role', 'inner')
            ->join('mstportal_roles ar', 'ar.id_role = a.id_role', 'left')
            ->where('a.id_user', $userId)
            ->order_by('a.is_default', 'DESC')
            ->order_by('s.shipper_name', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['id_shipper'] = (int) $row['id_shipper'];
            $row['is_default'] = (bool) $row['is_default'];
            $row['is_active'] = (bool) $row['is_active'];
        }
        unset($row);
        return $rows;
    }

    private function castUserRow(array $row, array $accesses, int $currentUserId): array
    {
        $row['id_user'] = (int) $row['id_user'];
        $row['id_shipper'] = (int) $row['id_shipper'];
        $row['id_role'] = (int) $row['id_role'];
        $row['is_primary'] = (bool) $row['is_primary'];
        $row['is_active'] = (bool) $row['is_active'];
        $row['must_change_password'] = (bool) $row['must_change_password'];
        $row['token_version'] = (int) $row['token_version'];
        $row['is_current_user'] = $row['id_user'] === $currentUserId;
        $row['shipper_access'] = $accesses ?: [[
            'id_shipper' => $row['id_shipper'],
            'shipper_code' => null,
            'shipper_name' => null,
            'is_default' => true,
            'is_active' => $row['is_active'],
            'role_code' => $row['role_code'],
        ]];
        $row['shipper_ids'] = array_values(array_map(
            static fn(array $x): int => (int) $x['id_shipper'],
            $row['shipper_access']
        ));
        return $row;
    }

    private function getAssignableRoles(string $actorRoleCode): array
    {
        $this->db
            ->select('id_role, role_code, role_name, description')
            ->from('mstportal_roles')
            ->where('is_active', 1);
        if ($actorRoleCode !== 'OWNER') {
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
        return $rows;
    }

    private function findAssignableRole(string $roleCode, string $actorRoleCode): ?array
    {
        if ($roleCode === 'OWNER' && $actorRoleCode !== 'OWNER') {
            return null;
        }
        $row = $this->db
            ->select('id_role, role_code, role_name')
            ->from('mstportal_roles')
            ->where('role_code', $roleCode)
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();
        return $row ?: null;
    }

    private function validateShipperSelection(array $auth, array $data, ?array $target = null): array
    {
        $allowedIds = array_values(array_unique(array_map('intval', $auth['shipper_ids'])));
        $requestedIds = $data['shipper_ids'] ?? ($target['shipper_ids'] ?? []);
        if (!is_array($requestedIds)) {
            $requestedIds = [$requestedIds];
        }
        $requestedIds = array_values(array_unique(array_filter(array_map('intval', $requestedIds))));
        if (!$requestedIds) {
            return ['ok' => false, 'message' => 'Pilih minimal satu shipper untuk user.'];
        }
        if (array_diff($requestedIds, $allowedIds)) {
            return ['ok' => false, 'message' => 'Ada shipper yang berada di luar akses Anda.'];
        }

        $defaultId = (int) ($data['default_shipper_id'] ?? ($target['id_shipper'] ?? 0));
        if ($defaultId <= 0 || !in_array($defaultId, $requestedIds, true)) {
            $defaultId = $requestedIds[0];
        }

        return [
            'ok' => true,
            'shipper_ids' => $requestedIds,
            'default_shipper_id' => $defaultId,
        ];
    }

    private function replaceUserShipperAccess(
        int $userId,
        array $shipperIds,
        int $defaultShipperId,
        int $roleId,
        int $actorUserId
    ): bool {
        if (!$this->db->table_exists('mstshipper_user_access')) {
            return true;
        }

        $this->db->where('id_user', $userId)->delete('mstshipper_user_access');
        foreach ($shipperIds as $shipperId) {
            $ok = $this->db->insert('mstshipper_user_access', [
                'id_user' => $userId,
                'id_shipper' => (int) $shipperId,
                'id_role' => $roleId,
                'is_default' => ((int) $shipperId === $defaultShipperId) ? 1 : 0,
                'is_active' => 1,
                'created_by_user_id' => $actorUserId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$ok) {
                log_message('error', 'replaceUserShipperAccess error: ' . json_encode($this->db->error()));
                return false;
            }
        }
        return true;
    }

    private function validateUserIdentity(
        string $username,
        string $email,
        string $fullName,
        string $password,
        string $confirmPassword,
        bool $validatePassword
    ): ?string {
        if ($username === '' || $fullName === '') {
            return 'Username dan nama user wajib diisi.';
        }
        if (!preg_match('/^[a-z0-9._-]{3,100}$/', $username)) {
            return 'Username minimal 3 karakter dan hanya boleh huruf kecil, angka, titik, garis bawah, atau strip.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Format email tidak valid.';
        }
        if ($validatePassword) {
            if ($password === '' || $confirmPassword === '') {
                return 'Password sementara dan konfirmasinya wajib diisi.';
            }
            if ($password !== $confirmPassword) {
                return 'Konfirmasi password tidak sama.';
            }
            if (!$this->isStrongPassword($password)) {
                return 'Password minimal 10 karakter, dengan huruf besar, huruf kecil, dan angka.';
            }
        }
        return null;
    }

    private function usernameExists(string $username, int $ignoreUserId = 0): bool
    {
        $this->db->from('mstshipper_users')->where('username', $username);
        if ($ignoreUserId > 0) {
            $this->db->where('id_user !=', $ignoreUserId);
        }
        return $this->db->count_all_results() > 0;
    }

    private function emailExists(string $email, int $ignoreUserId = 0): bool
    {
        $this->db->from('mstshipper_users')->where('email', $email);
        if ($ignoreUserId > 0) {
            $this->db->where('id_user !=', $ignoreUserId);
        }
        return $this->db->count_all_results() > 0;
    }

    private function normalizedIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        return $ids;
    }

    private function jsonInput(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password);
    }
}
