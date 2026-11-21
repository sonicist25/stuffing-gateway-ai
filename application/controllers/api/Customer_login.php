<?php
use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Customer_login extends RestController
{
    protected $methods = [
		'login_post'              => ['key' => false],
		'refresh_post'            => ['key' => false],
		'profile_get'             => ['key' => false],
		'change_password_post'    => ['key' => false],
		'request_access_post'     => ['key' => false],

		'impersonate_post'        => ['key' => false],
		'stop_impersonation_post' => ['key' => false],
	];

    /** @var string */
    private $jwt_key = '';

    private $access_ttl = 3600;
    private $refresh_ttl = 604800; // 7 hari
    private $max_login_attempts = 5;
    private $lock_minutes = 15;

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

        // Parameter TRUE mengembalikan instance DB, jadi harus di-assign.
        $this->db = $this->load->database('master', true);
        $this->load->model('api/m_customer_login');

        // Simpan secret di environment/config, jangan di source code.
        $this->jwt_key = trim((string) (getenv('GCL_JWT_KEY') ?: $this->config->item('gcl_jwt_key')));
    }

    /** POST /customer_login/login */
    public function login_post()
    {
        if (!$this->ensureJwtConfigured()) {
            return;
        }

        $params = $this->jsonInput();
        $login = trim((string) ($params['username'] ?? $params['shipper_id'] ?? ''));
        $password = (string) ($params['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->response([
                'status' => false,
                'message' => 'Username/email dan password wajib diisi.',
            ], 400);
            return;
        }

        $user = $this->findUserByLogin($login);

        // Kompatibilitas login lama. Pada login pertama, kredensial mstshipper
        // otomatis dipindahkan menjadi akun OWNER dengan password_hash.
        if (!$user) {
            $user = $this->migrateLegacyLogin($login, $password);
        }

        if (!$user || (int) $user['user_active'] !== 1 || $user['shipper_active'] !== 'Yes') {
            $this->response([
                'status' => false,
                'message' => 'Username/email atau password tidak valid.',
            ], 401);
            return;
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {

			$lockedUntil = strtotime($user['locked_until']);
			$retryAfter = max(1, $lockedUntil - time());

			$this->output->set_header('Retry-After: ' . $retryAfter);

			$this->response([
				'status' => false,
				'code' => 'ACCOUNT_TEMPORARILY_LOCKED',
				'message' => 'Akun terkunci sementara karena terlalu banyak percobaan login.',
				'locked_until' => $user['locked_until'],
				'retry_after' => $retryAfter,
			], 429);

			return;
		}

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin((int) $user['id_user'], (int) $user['failed_login_count']);
            $this->response([
                'status' => false,
                'message' => 'Username/email atau password tidak valid.',
            ], 401);
            return;
        }

        $passwordHash = $user['password_hash'];
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->db->where('id_user', (int) $user['id_user'])->update('mstshipper_users', [
            'password_hash' => $passwordHash,
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->input->ip_address(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user['password_hash'] = $passwordHash;
        $permissions = $this->getEffectivePermissions((int) $user['id_user'], (int) $user['id_role']);
        $shipperAccess = $this->getAccessibleShippers((int) $user['id_user']);
        $tokens = $this->issueTokens($user, $permissions, $shipperAccess);

        $this->response([
            'status' => true,
            'message' => 'Login successful',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_ttl,
            'must_change_password' => (bool) $user['must_change_password'],
            'user' => $this->publicUser($user, $permissions, $shipperAccess),
        ], 200);
    }

    /** POST /customer_login/refresh */
    public function refresh_post()
    {
        if (!$this->ensureJwtConfigured()) {
            return;
        }

        $params = $this->jsonInput();
        $refreshToken = trim((string) ($params['refresh_token'] ?? ''));

        if ($refreshToken === '') {
            $this->response([
                'status' => false,
                'message' => 'Missing refresh token.',
            ], 400);
            return;
        }

        try {
            $decoded = JWT::decode(
                $refreshToken,
                new Key($this->jwt_key, 'HS256')
            );

            if (($decoded->type ?? '') !== 'refresh') {
                throw new RuntimeException('Invalid token type.');
            }

            $userId = (int) ($decoded->data->id_user ?? 0);
            $tokenVersion = (int) ($decoded->data->token_version ?? 0);

            if ($userId <= 0) {
                throw new RuntimeException('Invalid user in refresh token.');
            }

            $user = $this->findUserById($userId);

            if (
                !$user ||
                (int) $user['user_active'] !== 1 ||
                $user['shipper_active'] !== 'Yes' ||
                (int) $user['token_version'] !== $tokenVersion
            ) {
                throw new RuntimeException('Token has been revoked.');
            }

            // Penting: metadata impersonation dibaca SEBELUM issueTokens().
            // Dengan demikian auto-refresh tidak menghilangkan mode impersonation.
            $impersonation = $this->extractImpersonationFromDecoded($decoded);

            if ($impersonation !== null) {
                $this->validateImpersonationContext(
                    $impersonation,
                    (int) $user['id_user']
                );
            }

            $permissions = $this->getEffectivePermissions(
                (int) $user['id_user'],
                (int) $user['id_role']
            );

            $shipperAccess = $this->getAccessibleShippers(
                (int) $user['id_user']
            );

            if (!$shipperAccess) {
                throw new RuntimeException(
                    'User tidak memiliki akses shipper aktif.'
                );
            }

            $tokens = $this->issueTokens(
                $user,
                $permissions,
                $shipperAccess,
                $impersonation
            );

            $this->response([
                'status' => true,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $this->access_ttl,

                // Admin yang sedang impersonate tidak boleh diarahkan ke
                // halaman ganti password milik customer.
                'must_change_password' => $impersonation !== null
                    ? false
                    : (bool) $user['must_change_password'],

                'impersonation' => $impersonation,
                'user' => $this->publicUser(
                    $user,
                    $permissions,
                    $shipperAccess
                ),
            ], 200);
        } catch (Throwable $e) {
            log_message(
                'error',
                'Customer_login/refresh: ' . $e->getMessage()
            );

            $this->response([
                'status' => false,
                'message' => 'Invalid or expired refresh token.',
            ], 401);
        }
    }

    /** GET /customer_login/profile */
    public function profile_get()
    {
        $auth = $this->authenticateAccessToken();
        if (!$auth) {
            return;
        }

        $user = $auth['user'];
        $permissions = $auth['permissions'];
        $shipperAccess = $auth['shipper_access'];
        $requestedShipperId = (int) ($this->input->get('shipper_id') ?: 0);
        $idShipper = $this->resolveAccessibleShipperId(
            $requestedShipperId,
            (int) $user['id_shipper'],
            $shipperAccess
        );

        if ($idShipper <= 0) {
            $this->response([
                'status' => false,
                'message' => 'Shipper tidak termasuk dalam akses user ini.',
            ], 403);
            return;
        }

        try {
            $this->load->driver('cache', ['adapter' => 'redis', 'backup' => 'file']);

            // Data dashboard tetap dicache per perusahaan. Informasi current user
            // tidak dimasukkan ke cache agar tidak tertukar antar-user.
            $cacheKey = 'dashboard_shipper_' . $idShipper;
            $dashboardData = $this->cache->get($cacheKey);

            if (!$dashboardData) {
                $dashboardData = [
                    'id_shipper' => $idShipper,
                    'shipper_detail' => $this->m_customer_login->get_shipper_detail($idShipper),
                    'total_shipment' => $this->m_customer_login->get_total_shipment($idShipper),
                    'open_invoices' => $this->m_customer_login->get_open_invoices($idShipper),
                    'latest_invoices' => $this->m_customer_login->get_latest_invoices($idShipper),
                    'volume_chart' => $this->m_customer_login->get_volume_chart($idShipper),
                    'last_bookings' => $this->m_customer_login->get_last_bookings($idShipper),
                    'spending_bookings' => $this->m_customer_login->get_sum_price_per_month($idShipper),
                ];

                $this->cache->save($cacheKey, $dashboardData, 900);
            }

            $dashboardData['active_shipper_id'] = $idShipper;
            $dashboardData['accessible_shippers'] = $shipperAccess;
            $dashboardData['current_user'] = $this->publicUser($user, $permissions, $shipperAccess);
            $dashboardData['impersonation'] = $auth['impersonation'] ?? null;

            $this->response([
                'status' => true,
                'data' => $dashboardData,
            ], 200);
        } catch (Throwable $e) {
            log_message('error', 'Customer profile error: ' . $e->getMessage());
            $this->response([
                'status' => false,
                'message' => 'Server error saat mengambil profile.',
            ], 500);
        }
    }

    /**
     * GET /customer_login/firmenich_dashboard?month=YYYY-MM&shipper_id=4407
     *
     * Dashboard khusus DSM-Firmenich. Data mengikuti satu perusahaan Firmenich
     * yang sedang dipilih user (4407 atau 6412) dan tetap divalidasi terhadap akses user.
     */
    public function firmenich_dashboard_get()
    {
        $auth = $this->authenticateAccessToken();
        if (!$auth) {
            return;
        }

        $firmenichShipperIds = [4407, 6412];
        $accessibleShipperIds = array_values(array_unique(array_filter(array_map(
            static function ($row) {
                return (int) ($row['id_shipper'] ?? 0);
            },
            $auth['shipper_access']
        ))));

        // Hanya shipper Firmenich yang memang termasuk akses user.
        $availableFirmenichIds = array_values(array_intersect(
            $firmenichShipperIds,
            $accessibleShipperIds
        ));

        if (!$availableFirmenichIds) {
            $this->response([
                'status' => false,
                'message' => 'Dashboard Firmenich tidak tersedia untuk user ini.',
            ], 403);
            return;
        }

        // Dashboard monthly mengikuti perusahaan yang sedang dipilih di FE.
        // Jika shipper_id tidak dikirim (backward compatibility), gunakan
        // default shipper user jika termasuk Firmenich; jika tidak, ambil
        // Firmenich pertama yang memang boleh diakses.
        $requestedShipperId = (int) ($this->input->get('shipper_id') ?: 0);

        if ($requestedShipperId <= 0) {
            $defaultShipperId = (int) ($auth['user']['id_shipper'] ?? 0);
            $requestedShipperId = in_array($defaultShipperId, $availableFirmenichIds, true)
                ? $defaultShipperId
                : (int) $availableFirmenichIds[0];
        }

        if (!in_array($requestedShipperId, $firmenichShipperIds, true)) {
            $this->response([
                'status' => false,
                'message' => 'Shipper yang dipilih bukan perusahaan Firmenich.',
            ], 422);
            return;
        }

        if (!in_array($requestedShipperId, $availableFirmenichIds, true)) {
            $this->response([
                'status' => false,
                'message' => 'Shipper tidak termasuk dalam akses user ini.',
            ], 403);
            return;
        }

        // Penting: query hanya untuk SATU perusahaan yang sedang dipilih.
        $shipperIds = [$requestedShipperId];

        $month = trim((string) ($this->input->get('month') ?: date('Y-m')));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->response([
                'status' => false,
                'message' => 'Format month harus YYYY-MM.',
            ], 422);
            return;
        }

        $periodStart = DateTime::createFromFormat('!Y-m-d', $month . '-01');
        if (!$periodStart) {
            $this->response([
                'status' => false,
                'message' => 'Month tidak valid.',
            ], 422);
            return;
        }

        $dateFrom = $periodStart->format('Y-m-d');
        $dateToExclusive = (clone $periodStart)
            ->modify('first day of next month')
            ->format('Y-m-d');

        try {
            $topBilling = $this->m_customer_login->get_firmenich_top_destination_billing(
                $shipperIds,
                $dateFrom,
                $dateToExclusive,
                10
            );

            $topCbm = $this->m_customer_login->get_firmenich_top_destination_cbm(
                $shipperIds,
                $dateFrom,
                $dateToExclusive,
                10
            );

            $this->response([
                'status' => true,
                'data' => [
                    'month' => $month,
                    'period' => [
                        'date_from' => $dateFrom,
                        'date_to_exclusive' => $dateToExclusive,
                    ],
                    'selected_shipper_id' => $requestedShipperId,
                    'shipper_ids' => $shipperIds,
                    'top_destination_billing' => $topBilling,
                    'top_destination_cbm' => $topCbm,
                ],
            ], 200);
        } catch (Throwable $e) {
            log_message('error', 'Firmenich dashboard error: ' . $e->getMessage());
            $this->response([
                'status' => false,
                'message' => 'Server error saat mengambil dashboard Firmenich.',
            ], 500);
        }
    }

    /** POST /customer_login/change_password */
    public function change_password_post()
    {
        $auth = $this->authenticateAccessToken();
        if (!$auth) {
            return;
        }

        $params = $this->jsonInput();
        $currentPassword = (string) ($params['current_password'] ?? '');
        $newPassword = (string) ($params['new_password'] ?? '');
        $confirmPassword = (string) ($params['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->response([
                'status' => false,
                'message' => 'Current password, new password, dan confirmation wajib diisi.',
            ], 400);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->response([
                'status' => false,
                'message' => 'Konfirmasi password tidak sama.',
            ], 422);
            return;
        }

        if (!$this->isStrongPassword($newPassword)) {
            $this->response([
                'status' => false,
                'message' => 'Password minimal 10 karakter dan harus memiliki huruf besar, huruf kecil, serta angka.',
            ], 422);
            return;
        }

        $user = $auth['user'];
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->response([
                'status' => false,
                'message' => 'Current password tidak benar.',
            ], 401);
            return;
        }

        if (password_verify($newPassword, $user['password_hash'])) {
            $this->response([
                'status' => false,
                'message' => 'Password baru tidak boleh sama dengan password lama.',
            ], 422);
            return;
        }

        $newTokenVersion = (int) $user['token_version'] + 1;
        $updated = $this->db->where('id_user', (int) $user['id_user'])->update('mstshipper_users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'password_reset_token_hash' => null,
            'password_reset_expires_at' => null,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => $newTokenVersion,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            $this->response([
                'status' => false,
                'message' => 'Gagal memperbarui password.',
            ], 500);
            return;
        }

        // Token lama menjadi tidak berlaku. Kirim token baru agar user tidak perlu login ulang.
        $user = $this->findUserById((int) $user['id_user']);
        $permissions = $this->getEffectivePermissions((int) $user['id_user'], (int) $user['id_role']);
        $shipperAccess = $this->getAccessibleShippers((int) $user['id_user']);
        $tokens = $this->issueTokens($user, $permissions, $shipperAccess);

        $this->response([
            'status' => true,
            'message' => 'Password berhasil diperbarui.',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_ttl,
            'must_change_password' => false,
            'user' => $this->publicUser($user, $permissions, $shipperAccess),
        ], 200);
    }

    /** POST /customer_login/request_access */
    public function request_access_post()
    {
        try {
            $data = $this->jsonInput();
            $companyName = trim((string) ($data['company_name'] ?? ''));
            $userName = trim((string) ($data['user_name'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if ($companyName === '' || $userName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response([
                    'status' => false,
                    'code' => 'INVALID_REQUEST_ACCESS_PAYLOAD',
                    'message' => 'Company name, contact name, dan email valid wajib diisi.',
                ], 422);
                return;
            }

            $apiKey = $this->getElasticEmailApiKey();
            if ($apiKey === '') {
                log_message('error', 'Customer_login/request_access: ELASTIC_EMAIL_API_KEY belum dikonfigurasi.');
                $this->response([
                    'status' => false,
                    'code' => 'EMAIL_SERVICE_NOT_CONFIGURED',
                    'message' => 'Email service belum dikonfigurasi.',
                ], 503);
                return;
            }

            if (!function_exists('curl_init')) {
                log_message('error', 'Customer_login/request_access: ekstensi PHP cURL tidak tersedia.');
                $this->response([
                    'status' => false,
                    'code' => 'CURL_EXTENSION_UNAVAILABLE',
                    'message' => 'Email service tidak tersedia pada server.',
                ], 503);
                return;
            }

            $safeCompany = htmlspecialchars($companyName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeUser = htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $htmlContent = "
                <div style='font-family:Arial,sans-serif;padding:20px;color:#333'>
                    <h2 style='color:#0f4fd8'>New Portal Access Request</h2>
                    <p>A customer has requested access to the Gateway Customer Portal.</p>
                    <table border='0' cellpadding='5' cellspacing='0'>
                        <tr><td><strong>Company Name</strong></td><td>: {$safeCompany}</td></tr>
                        <tr><td><strong>Contact Name</strong></td><td>: {$safeUser}</td></tr>
                        <tr><td><strong>Email Address</strong></td><td>: {$safeEmail}</td></tr>
                    </table>
                </div>";

            $payload = [
                'Recipients' => [
                    'To' => [$this->getAccessRequestReceiver()],
                ],
                'Content' => [
                    'Body' => [[
                        'ContentType' => 'HTML',
                        'Charset' => 'utf-8',
                        'Content' => $htmlContent,
                    ]],
                    'From' => $this->getAccessRequestSender(),
                    'Subject' => 'New Access Request: ' . $companyName,
                ],
            ];

            $jsonPayload = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            if ($jsonPayload === false) {
                log_message(
                    'error',
                    'Customer_login/request_access: gagal encode payload: ' . json_last_error_msg()
                );
                $this->response([
                    'status' => false,
                    'code' => 'EMAIL_PAYLOAD_ENCODING_FAILED',
                    'message' => 'Gagal menyiapkan access request.',
                ], 500);
                return;
            }

            $ch = curl_init('https://api.elasticemail.com/v4/emails/transactional');
            if ($ch === false) {
                throw new RuntimeException('curl_init gagal dibuat.');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-ElasticEmail-ApiKey: ' . $apiKey,
                ],
            ]);

            $responseBody = curl_exec($ch);
            $curlErrorNo = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($responseBody === false || $curlErrorNo !== 0) {
                log_message(
                    'error',
                    'Customer_login/request_access: ElasticEmail cURL error ' .
                    $curlErrorNo . ' - ' . $curlError
                );
                $this->response([
                    'status' => false,
                    'code' => 'EMAIL_PROVIDER_CONNECTION_FAILED',
                    'message' => 'Tidak dapat terhubung ke email service.',
                ], 502);
                return;
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                log_message(
                    'error',
                    'Customer_login/request_access: ElasticEmail HTTP ' . $httpCode .
                    ' response=' . substr((string) $responseBody, 0, 2000)
                );
                $this->response([
                    'status' => false,
                    'code' => 'EMAIL_PROVIDER_REJECTED_REQUEST',
                    'message' => 'Email service menolak access request.',
                ], 502);
                return;
            }

            log_message(
                'info',
                'Customer_login/request_access: request berhasil dikirim untuk ' . $email
            );

            $this->response([
                'status' => true,
                'message' => 'Request berhasil dikirim.',
            ], 200);
        } catch (Throwable $e) {
            log_message(
                'error',
                'Customer_login/request_access exception: ' . $e->getMessage() .
                ' in ' . $e->getFile() . ':' . $e->getLine()
            );

            $this->response([
                'status' => false,
                'code' => 'REQUEST_ACCESS_INTERNAL_ERROR',
                'message' => 'Terjadi kesalahan saat mengirim access request.',
            ], 500);
        }
    }

    /**
     * Environment lebih diprioritaskan. Fallback config berguna pada PHP-FPM
     * yang tidak meneruskan environment variables secara default.
     */
    private function getElasticEmailApiKey()
    {
        $envValue = getenv('ELASTIC_EMAIL_API_KEY');
        if ($envValue !== false && trim((string) $envValue) !== '') {
            return trim((string) $envValue);
        }

        return trim((string) $this->config->item('elastic_email_api_key'));
    }

    private function getAccessRequestSender()
    {
        $configured = trim((string) $this->config->item('access_request_sender'));
        return $configured !== '' ? $configured : 'no-reply@gcl.digital';
    }

    private function getAccessRequestReceiver()
    {
        $configured = trim((string) $this->config->item('access_request_receiver'));
        return filter_var($configured, FILTER_VALIDATE_EMAIL)
            ? $configured
            : 'sony@gateway-id.com';
    }

    private function jsonInput()
    {
        $data = json_decode((string) file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function ensureJwtConfigured()
    {
        if ($this->jwt_key !== '') {
            return true;
        }

        log_message('error', 'GCL_JWT_KEY belum dikonfigurasi.');
        $this->response([
            'status' => false,
            'message' => 'Authentication service belum dikonfigurasi.',
        ], 500);
        return false;
    }

    private function findUserByLogin($login)
    {
        $this->db->select([
            'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
            'u.password_hash', 'u.full_name', 'u.phone', 'u.job_title',
            'u.is_primary', 'u.is_active AS user_active', 'u.must_change_password',
            'u.failed_login_count', 'u.locked_until', 'u.token_version',
            'r.role_code', 'r.role_name',
            's.shipper_id AS shipper_code', 's.shipper_name', 's.region_id',
            's.isActive AS shipper_active',
        ]);
        $this->db->from('mstshipper_users u');
        $this->db->join('mstshipper s', 's.id_shipper = u.id_shipper', 'inner');
        $this->db->join('mstportal_roles r', 'r.id_role = u.id_role AND r.is_active = 1', 'inner');
        $this->db->group_start();
        $this->db->where('u.username', $login);
        $this->db->or_where('u.email', $login);
        $this->db->group_end();
        $this->db->where('u.deleted_at IS NULL', null, false);
        $this->db->limit(1);
        return $this->db->get()->row_array() ?: null;
    }

    private function findUserById($userId)
    {
        $this->db->select([
            'u.id_user', 'u.id_shipper', 'u.id_role', 'u.username', 'u.email',
            'u.password_hash', 'u.full_name', 'u.phone', 'u.job_title',
            'u.is_primary', 'u.is_active AS user_active', 'u.must_change_password',
            'u.failed_login_count', 'u.locked_until', 'u.token_version',
            'r.role_code', 'r.role_name',
            's.shipper_id AS shipper_code', 's.shipper_name', 's.region_id',
            's.isActive AS shipper_active',
        ]);
        $this->db->from('mstshipper_users u');
        $this->db->join('mstshipper s', 's.id_shipper = u.id_shipper', 'inner');
        $this->db->join('mstportal_roles r', 'r.id_role = u.id_role AND r.is_active = 1', 'inner');
        $this->db->where('u.id_user', $userId);
        $this->db->where('u.deleted_at IS NULL', null, false);
        $this->db->limit(1);
        return $this->db->get()->row_array() ?: null;
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

    private function issueTokens(
        array $user,
        array $permissions,
        array $shipperAccess,
        ?array $impersonation = null
    ) {
        $now = time();

        $shipperIds = array_values(array_map(
            static function ($row) {
                return (int) $row['id_shipper'];
            },
            $shipperAccess
        ));

        $tokenData = [
            'id_user' => (int) $user['id_user'],
            'id_shipper' => (int) $user['id_shipper'],
            'shipper_ids' => $shipperIds,
            'accessible_shippers' => $shipperAccess,
            'shipper_code' => $user['shipper_code'],
            'shipper_name' => $user['shipper_name'],
            'region_id' => $user['region_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role_code' => $user['role_code'],
            'permissions' => $permissions,
            'token_version' => (int) $user['token_version'],
        ];

        if ($impersonation !== null) {
            $tokenData['impersonation'] = $impersonation;
        }

        $accessPayload = [
            'iss' => 'gateway-id.com',
            'aud' => 'react-client',
            'type' => 'access',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->access_ttl,
            'data' => $tokenData,
        ];

        $refreshData = [
            'id_user' => (int) $user['id_user'],
            'id_shipper' => (int) $user['id_shipper'],
            'shipper_ids' => $shipperIds,
            'token_version' => (int) $user['token_version'],
        ];

        if ($impersonation !== null) {
            $refreshData['impersonation'] = $impersonation;
        }

        $refreshPayload = [
            'iss' => 'gateway-id.com',
            'aud' => 'react-client',
            'type' => 'refresh',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->refresh_ttl,
            'data' => $refreshData,
        ];

        return [
            'access_token' => JWT::encode(
                $accessPayload,
                $this->jwt_key,
                'HS256'
            ),
            'refresh_token' => JWT::encode(
                $refreshPayload,
                $this->jwt_key,
                'HS256'
            ),
        ];
    }

    private function authenticateAccessToken($requiredPermission = null)
    {
        if (!$this->ensureJwtConfigured()) {
            return null;
        }

        $authHeader = trim(
            (string) $this->input->get_request_header('Authorization')
        );

        if (stripos($authHeader, 'Bearer ') !== 0) {
            $this->response([
                'status' => false,
                'message' => 'Missing or invalid Authorization header.',
            ], 401);
            return null;
        }

        try {
            $decoded = JWT::decode(
                substr($authHeader, 7),
                new Key($this->jwt_key, 'HS256')
            );

            if (($decoded->type ?? '') !== 'access') {
                throw new RuntimeException('Invalid token type.');
            }

            $userId = (int) ($decoded->data->id_user ?? 0);
            $tokenVersion = (int) ($decoded->data->token_version ?? 0);

            if ($userId <= 0) {
                throw new RuntimeException('Invalid token user.');
            }

            $user = $this->findUserById($userId);

            if (
                !$user ||
                (int) $user['user_active'] !== 1 ||
                $user['shipper_active'] !== 'Yes' ||
                (int) $user['token_version'] !== $tokenVersion
            ) {
                throw new RuntimeException('Token has been revoked.');
            }

            $impersonation = $this->extractImpersonationFromDecoded($decoded);

            if ($impersonation !== null) {
                $this->validateImpersonationContext(
                    $impersonation,
                    (int) $user['id_user']
                );
            }

            $permissions = $this->getEffectivePermissions(
                (int) $user['id_user'],
                (int) $user['id_role']
            );

            if (
                $requiredPermission !== null &&
                !in_array($requiredPermission, $permissions, true)
            ) {
                $this->response([
                    'status' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
                return null;
            }

            $shipperAccess = $this->getAccessibleShippers(
                (int) $user['id_user']
            );

            if (!$shipperAccess) {
                throw new RuntimeException(
                    'User tidak memiliki akses shipper aktif.'
                );
            }

            return [
                'user' => $user,
                'permissions' => $permissions,
                'shipper_access' => $shipperAccess,
                'impersonation' => $impersonation,
            ];
        } catch (Throwable $e) {
            log_message(
                'error',
                'Customer_login/access_token: ' . $e->getMessage()
            );

            $this->response([
                'status' => false,
                'message' => 'Invalid or expired access token.',
            ], 401);

            return null;
        }
    }


    /**
     * Ambil metadata impersonation dari JWT yang sudah terverifikasi signature-nya.
     */
    private function extractImpersonationFromDecoded($decoded)
    {
        if (empty($decoded->data->impersonation)) {
            return null;
        }

        $json = json_encode($decoded->data->impersonation);

        if ($json === false) {
            throw new RuntimeException(
                'Invalid impersonation metadata.'
            );
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid impersonation metadata.'
            );
        }

        if (
            empty($data['is_active']) ||
            (int) ($data['audit_id'] ?? 0) <= 0 ||
            (int) ($data['impersonator_user_id'] ?? 0) <= 0 ||
            (int) ($data['target_user_id'] ?? 0) <= 0
        ) {
            throw new RuntimeException(
                'Incomplete impersonation metadata.'
            );
        }

        $data['is_active'] = true;
        $data['audit_id'] = (int) $data['audit_id'];
        $data['impersonator_user_id'] =
            (int) $data['impersonator_user_id'];
        $data['target_user_id'] =
            (int) $data['target_user_id'];

        if (isset($data['target_shipper_id'])) {
            $data['target_shipper_id'] =
                (int) $data['target_shipper_id'];
        }

        return $data;
    }

    /**
     * Jangan hanya percaya payload JWT. Audit row dan akun admin asli juga
     * harus masih aktif. Ini membuat permission support bisa dicabut kapan saja.
     */
    private function validateImpersonationContext(
        array $impersonation,
        $currentUserId
    ) {
        $currentUserId = (int) $currentUserId;

        if (
            $currentUserId <= 0 ||
            (int) $impersonation['target_user_id'] !== $currentUserId
        ) {
            throw new RuntimeException(
                'Impersonation target mismatch.'
            );
        }

        if (!$this->db->table_exists('customer_impersonation_logs')) {
            throw new RuntimeException(
                'Impersonation audit table is unavailable.'
            );
        }

        $audit = $this->db
            ->select(
                'id, impersonator_user_id, target_user_id, ' .
                'target_shipper_id, started_at, ended_at'
            )
            ->from('customer_impersonation_logs')
            ->where('id', (int) $impersonation['audit_id'])
            ->limit(1)
            ->get()
            ->row_array();

        if (!$audit) {
            throw new RuntimeException(
                'Impersonation audit record not found.'
            );
        }

        if (!empty($audit['ended_at'])) {
            throw new RuntimeException(
                'Impersonation session has ended.'
            );
        }

        if (
            (int) $audit['impersonator_user_id'] !==
                (int) $impersonation['impersonator_user_id'] ||
            (int) $audit['target_user_id'] !== $currentUserId
        ) {
            throw new RuntimeException(
                'Impersonation audit mismatch.'
            );
        }

        $admin = $this->findUserById(
            (int) $impersonation['impersonator_user_id']
        );

        if (
            !$admin ||
            (int) $admin['user_active'] !== 1 ||
            $admin['shipper_active'] !== 'Yes'
        ) {
            throw new RuntimeException(
                'Impersonator account is inactive.'
            );
        }

        $adminPermissions = $this->getEffectivePermissions(
            (int) $admin['id_user'],
            (int) $admin['id_role']
        );

        if (
            !in_array(
                'support.impersonate',
                $adminPermissions,
                true
            ) ||
            !$this->hasDirectPermissionAllow(
                (int) $admin['id_user'],
                'support.impersonate'
            )
        ) {
            throw new RuntimeException(
                'Impersonator permission has been revoked.'
            );
        }

        return true;
    }

    /**
     * Impersonation adalah permission support yang sensitif.
     * Wajib ALLOW langsung di mstshipper_user_permission_overrides.
     */
    private function hasDirectPermissionAllow($userId, $permissionCode)
    {
        $userId = (int) $userId;
        $permissionCode = trim((string) $permissionCode);

        if ($userId <= 0 || $permissionCode === '') {
            return false;
        }

        return $this->db
            ->from('mstshipper_user_permission_overrides o')
            ->join(
                'mstportal_permissions p',
                'p.id_permission = o.id_permission AND p.is_active = 1',
                'inner'
            )
            ->where('o.id_user', $userId)
            ->where('o.access_type', 'ALLOW')
            ->where('p.permission_code', $permissionCode)
            ->count_all_results() > 0;
    }

    private function publicUser(array $user, array $permissions, array $shipperAccess)
    {
        return [
            'id_user' => (int) $user['id_user'],
            'id_shipper' => (int) $user['id_shipper'],
            'shipper_ids' => array_values(array_map(static function ($row) {
                return (int) $row['id_shipper'];
            }, $shipperAccess)),
            'accessible_shippers' => $shipperAccess,
            'shipper_code' => $user['shipper_code'],
            'shipper_name' => $user['shipper_name'],
            'region_id' => $user['region_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'job_title' => $user['job_title'],
            'role_code' => $user['role_code'],
            'role_name' => $user['role_name'],
            'is_primary' => (bool) $user['is_primary'],
            'must_change_password' => (bool) $user['must_change_password'],
            'permissions' => $permissions,
        ];
    }

    /**
     * Daftar mstshipper yang boleh diakses user. mstshipper_users.id_shipper
     * tetap dipakai sebagai default agar endpoint lama tetap kompatibel.
     */
    private function getAccessibleShippers($userId)
    {
        if (!$this->db->table_exists('mstshipper_user_access')) {
            $fallback = $this->findUserById((int) $userId);
            if (!$fallback) {
                return [];
            }

            return [[
                'id_shipper' => (int) $fallback['id_shipper'],
                'shipper_code' => $fallback['shipper_code'],
                'shipper_name' => $fallback['shipper_name'],
                'region_id' => $fallback['region_id'],
                'is_default' => true,
                'role_code' => $fallback['role_code'],
                'role_name' => $fallback['role_name'],
            ]];
        }

        $rows = $this->db
            ->select([
                'a.id_shipper', 'a.is_default',
                's.shipper_id AS shipper_code', 's.shipper_name', 's.region_id',
                'COALESCE(ar.role_code, ur.role_code) AS role_code',
                'COALESCE(ar.role_name, ur.role_name) AS role_name',
            ])
            ->from('mstshipper_user_access a')
            ->join('mstshipper_users u', 'u.id_user = a.id_user', 'inner')
            ->join('mstshipper s', "s.id_shipper = a.id_shipper AND s.isActive = 'Yes'", 'inner')
            ->join('mstportal_roles ur', 'ur.id_role = u.id_role AND ur.is_active = 1', 'inner')
            ->join('mstportal_roles ar', 'ar.id_role = a.id_role AND ar.is_active = 1', 'left')
            ->where('a.id_user', (int) $userId)
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

    private function resolveAccessibleShipperId($requestedShipperId, $defaultShipperId, array $shipperAccess)
    {
        $candidate = $requestedShipperId > 0 ? $requestedShipperId : $defaultShipperId;

        foreach ($shipperAccess as $row) {
            if ((int) $row['id_shipper'] === (int) $candidate) {
                return (int) $candidate;
            }
        }

        foreach ($shipperAccess as $row) {
            if (!empty($row['is_default'])) {
                return (int) $row['id_shipper'];
            }
        }

        return 0;
    }

    private function recordFailedLogin($userId, $currentAttempts)
    {
        $attempts = $currentAttempts + 1;
        $update = [
            'failed_login_count' => $attempts,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($attempts >= $this->max_login_attempts) {
            $update['locked_until'] = date('Y-m-d H:i:s', strtotime('+' . $this->lock_minutes . ' minutes'));
        }

        $this->db->where('id_user', $userId)->update('mstshipper_users', $update);
    }

    private function isStrongPassword($password)
    {
        return strlen($password) >= 10
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password);
    }

    /**
     * Migrasi otomatis satu akun legacy per shipper saat login pertama.
     * Hapus method ini setelah seluruh mstshipper sudah selesai dimigrasikan.
     */
    private function migrateLegacyLogin($login, $password)
    {
        $legacy = $this->db
            ->select('id_shipper, shipper_id, shipper_name, cp_name, cp_email, region_id, isActive, password_no_encript')
            ->from('mstshipper')
            ->where('shipper_id', $login)
            ->where('isActive', 'Yes')
            ->where('password_no_encript IS NOT NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$legacy || !hash_equals((string) $legacy['password_no_encript'], $password)) {
            return null;
        }

        $role = $this->db
            ->select('id_role')
            ->from('mstportal_roles')
            ->where('role_code', 'OWNER')
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$role) {
            log_message('error', 'Role OWNER belum tersedia untuk legacy migration.');
            return null;
        }

        $email = filter_var($legacy['cp_email'], FILTER_VALIDATE_EMAIL) ? strtolower($legacy['cp_email']) : null;
        if ($email !== null) {
            $emailExists = $this->db->where('email', $email)->count_all_results('mstshipper_users') > 0;
            if ($emailExists) {
                $email = null;
            }
        }

        $this->db->trans_begin();

        $inserted = $this->db->insert('mstshipper_users', [
            'id_shipper' => (int) $legacy['id_shipper'],
            'id_role' => (int) $role['id_role'],
            'username' => $legacy['shipper_id'],
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => trim((string) $legacy['cp_name']) ?: $legacy['shipper_name'],
            'is_primary' => 1,
            'is_active' => 1,
            'must_change_password' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$inserted) {
            $this->db->trans_rollback();
            log_message('error', 'Gagal migrasi login legacy shipper: ' . $login);
            return null;
        }

        $userId = (int) $this->db->insert_id();

        // Buat mapping akses default. Tabel ini juga memungkinkan satu akun
        // mengakses beberapa id_shipper, misalnya dua master milik Firmenich.
        $this->db->insert('mstshipper_user_access', [
            'id_user' => $userId,
            'id_shipper' => (int) $legacy['id_shipper'],
            'id_role' => (int) $role['id_role'],
            'is_default' => 1,
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Kredensial plaintext lama langsung dinonaktifkan setelah berhasil dipindah.
        $this->db->where('id_shipper', (int) $legacy['id_shipper'])->update('mstshipper', [
            'password' => null,
            'password_no_encript' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return null;
        }

        $this->db->trans_commit();
        return $this->findUserById($userId);
    }
	
    public function impersonate_post()
    {
        $auth = $this->authenticateAccessToken('support.impersonate');

        if (!$auth) {
            return;
        }

        // Security boundary: permission sensitif ini wajib diberikan sebagai
        // override ALLOW langsung ke user support, bukan diwariskan dari role.
        if (!$this->hasDirectPermissionAllow(
            (int) $auth['user']['id_user'],
            'support.impersonate'
        )) {
            $this->response([
                'status' => false,
                'message' => 'Permission impersonation harus diberikan langsung ke user support.',
            ], 403);
            return;
        }

        // Jangan izinkan impersonation berlapis.
        if (!empty($auth['impersonation'])) {
            $this->response([
                'status' => false,
                'code' => 'NESTED_IMPERSONATION_NOT_ALLOWED',
                'message' => 'Akhiri impersonation aktif terlebih dahulu.',
            ], 409);
            return;
        }

        if (!$this->db->table_exists('customer_impersonation_logs')) {
            $this->response([
                'status' => false,
                'code' => 'IMPERSONATION_AUDIT_TABLE_MISSING',
                'message' => 'Audit table impersonation belum tersedia.',
            ], 500);
            return;
        }

        $params = $this->jsonInput();

        $targetUserId = (int) ($params['target_user_id'] ?? 0);
        $reason = trim((string) ($params['reason'] ?? ''));

        if ($targetUserId <= 0) {
            $this->response([
                'status' => false,
                'message' => 'target_user_id wajib diisi.',
            ], 422);
            return;
        }

        if (strlen($reason) > 255) {
            $this->response([
                'status' => false,
                'message' => 'Reason maksimal 255 karakter.',
            ], 422);
            return;
        }

        $admin = $auth['user'];

        if ((int) $admin['id_user'] === $targetUserId) {
            $this->response([
                'status' => false,
                'message' => 'Tidak perlu impersonate akun sendiri.',
            ], 422);
            return;
        }

        $target = $this->findUserById($targetUserId);

        if (
            !$target ||
            (int) $target['user_active'] !== 1 ||
            $target['shipper_active'] !== 'Yes'
        ) {
            $this->response([
                'status' => false,
                'message' => 'Target user tidak aktif atau tidak ditemukan.',
            ], 404);
            return;
        }

        $targetPermissions = $this->getEffectivePermissions(
            (int) $target['id_user'],
            (int) $target['id_role']
        );

        $targetShipperAccess = $this->getAccessibleShippers(
            (int) $target['id_user']
        );

        if (!$targetShipperAccess) {
            $this->response([
                'status' => false,
                'message' => 'Target user tidak memiliki akses shipper.',
            ], 422);
            return;
        }

        $startedAt = date('Y-m-d H:i:s');

        $inserted = $this->db->insert(
            'customer_impersonation_logs',
            [
                'impersonator_user_id' => (int) $admin['id_user'],
                'target_user_id' => (int) $target['id_user'],
                'target_shipper_id' => (int) $target['id_shipper'],
                'reason' => $reason !== '' ? $reason : null,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => substr(
                    (string) $this->input->user_agent(),
                    0,
                    500
                ),
                'started_at' => $startedAt,
                'ended_at' => null,
            ]
        );

        if (!$inserted) {
            log_message(
                'error',
                'Customer_login/impersonate: gagal membuat audit log.'
            );

            $this->response([
                'status' => false,
                'message' => 'Gagal memulai impersonation.',
            ], 500);
            return;
        }

        $auditId = (int) $this->db->insert_id();

        if ($auditId <= 0) {
            $this->response([
                'status' => false,
                'message' => 'Audit impersonation tidak valid.',
            ], 500);
            return;
        }

        $impersonation = [
            'is_active' => true,
            'audit_id' => $auditId,

            'impersonator_user_id' => (int) $admin['id_user'],
            'impersonator_name' => (string) $admin['full_name'],
            'impersonator_email' => (string) $admin['email'],

            'target_user_id' => (int) $target['id_user'],
            'target_user_name' => (string) $target['full_name'],
            'target_user_email' => (string) $target['email'],
            'target_shipper_id' => (int) $target['id_shipper'],
            'target_shipper_name' => (string) $target['shipper_name'],

            'started_at' => $startedAt,
        ];

        $tokens = $this->issueTokens(
            $target,
            $targetPermissions,
            $targetShipperAccess,
            $impersonation
        );

        $this->response([
            'status' => true,
            'message' => 'Impersonation started.',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_ttl,

            // Jangan memaksa support mengganti password milik target.
            'must_change_password' => false,

            'impersonation' => $impersonation,
            'user' => $this->publicUser(
                $target,
                $targetPermissions,
                $targetShipperAccess
            ),
        ], 200);
    }


    public function stop_impersonation_post()
    {
        $auth = $this->authenticateAccessToken();

        if (!$auth) {
            return;
        }

        $impersonation = $auth['impersonation'] ?? null;

        if (
            !is_array($impersonation) ||
            empty($impersonation['is_active']) ||
            empty($impersonation['impersonator_user_id']) ||
            empty($impersonation['audit_id'])
        ) {
            $this->response([
                'status' => false,
                'code' => 'NOT_IMPERSONATING',
                'message' => 'Session saat ini bukan session impersonation.',
            ], 422);
            return;
        }

        $adminUserId = (int) $impersonation['impersonator_user_id'];
        $auditId = (int) $impersonation['audit_id'];

        $admin = $this->findUserById($adminUserId);

        if (
            !$admin ||
            (int) $admin['user_active'] !== 1 ||
            $admin['shipper_active'] !== 'Yes'
        ) {
            $this->response([
                'status' => false,
                'message' => 'Akun impersonator tidak aktif.',
            ], 403);
            return;
        }

        $adminPermissions = $this->getEffectivePermissions(
            (int) $admin['id_user'],
            (int) $admin['id_role']
        );

        if (
            !in_array(
                'support.impersonate',
                $adminPermissions,
                true
            ) ||
            !$this->hasDirectPermissionAllow(
                (int) $admin['id_user'],
                'support.impersonate'
            )
        ) {
            $this->response([
                'status' => false,
                'message' => 'Impersonator tidak lagi memiliki permission.',
            ], 403);
            return;
        }

        $adminShipperAccess = $this->getAccessibleShippers(
            (int) $admin['id_user']
        );

        if (!$adminShipperAccess) {
            $this->response([
                'status' => false,
                'message' => 'Akun impersonator tidak memiliki shipper aktif.',
            ], 403);
            return;
        }

        $endedAt = date('Y-m-d H:i:s');

        $updated = $this->db
            ->where('id', $auditId)
            ->where('impersonator_user_id', $adminUserId)
            ->where(
                'target_user_id',
                (int) ($impersonation['target_user_id'] ?? 0)
            )
            ->where('ended_at IS NULL', null, false)
            ->update(
                'customer_impersonation_logs',
                ['ended_at' => $endedAt]
            );

        if (!$updated) {
            log_message(
                'error',
                'Customer_login/stop_impersonation: gagal update audit_id=' .
                $auditId
            );

            $this->response([
                'status' => false,
                'message' => 'Gagal mengakhiri audit impersonation.',
            ], 500);
            return;
        }

        // Kembali menjadi token normal admin, TANPA metadata impersonation.
        $tokens = $this->issueTokens(
            $admin,
            $adminPermissions,
            $adminShipperAccess,
            null
        );

        $this->response([
            'status' => true,
            'message' => 'Impersonation ended.',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_ttl,
            'must_change_password' => (bool) $admin['must_change_password'],
            'impersonation' => null,
            'user' => $this->publicUser(
                $admin,
                $adminPermissions,
                $adminShipperAccess
            ),
        ], 200);
    }
}
