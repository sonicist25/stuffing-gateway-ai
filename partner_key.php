<?php
/**
 * Kelola partner key sister company di server GATEWAY, TANPA lewat routing CodeIgniter.
 * Letakkan di folder aplikasi Gateway (sebelah index.php), lalu jalankan dari terminal:
 *
 *   php partner_key.php status
 *   php partner_key.php generate OS        buat key acak, simpan hash, tampilkan key SEKALI
 *   php partner_key.php set OS <key>       pakai key sendiri (min. 32 karakter)
 *   php partner_key.php check OS <key>     cek key cocok atau tidak
 *   php partner_key.php revoke OS          cabut key
 *
 * Koneksi database dibaca dari application/config/database.php (grup 'master', atau
 * $active_group bila 'master' tidak ada). Bisa dipaksa: --app=/path/ke/application  --group=nama
 * Kompatibel PHP 7.x.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function fail($msg) { fwrite(STDERR, "ERROR: {$msg}\n"); exit(1); }

$args = array();
$opts = array('app' => __DIR__ . '/application', 'group' => '');
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--(app|group)=(.*)$/', $a, $m)) { $opts[$m[1]] = $m[2]; } else { $args[] = $a; }
}
$cmd  = isset($args[0]) ? $args[0] : 'status';
$code = isset($args[1]) ? strtoupper(trim($args[1])) : '';
$key  = isset($args[2]) ? $args[2] : '';

/* ---------- baca konfigurasi database CodeIgniter ---------- */
$app = rtrim($opts['app'], '/');
$env = getenv('CI_ENV') ?: 'production';
$cfgFile = is_file("{$app}/config/{$env}/database.php") ? "{$app}/config/{$env}/database.php" : "{$app}/config/database.php";
if (!is_file($cfgFile)) {
    fail("Tidak menemukan {$cfgFile}. Jalankan dari folder aplikasi Gateway (yang berisi index.php dan application/), atau pakai --app=/path/ke/application");
}
defined('BASEPATH')    || define('BASEPATH', __DIR__ . '/system/');
defined('APPPATH')     || define('APPPATH', $app . '/');
defined('ENVIRONMENT') || define('ENVIRONMENT', $env);
$db = array(); $active_group = 'default';
include $cfgFile;

$group = $opts['group'] !== '' ? $opts['group'] : (isset($db['master']) ? 'master' : $active_group);
if (!isset($db[$group])) {
    fail("Grup database '{$group}' tidak ada di {$cfgFile}. Grup tersedia: " . implode(', ', array_keys($db)));
}
$c    = $db[$group];
$host = $c['hostname'];
$port = !empty($c['port']) ? (int) $c['port'] : 3306;
if (strpos($host, ':') !== false && strpos($host, '/') === false) {
    list($host, $p) = explode(':', $host, 2);
    $port = (int) $p;
}

mysqli_report(MYSQLI_REPORT_OFF);
$my = @new mysqli($host, $c['username'], $c['password'], $c['database'], $port);
if ($my->connect_errno) {
    fail("Gagal konek ke database '{$c['database']}' (grup {$group}): {$my->connect_error}");
}
$my->set_charset(!empty($c['char_set']) ? $c['char_set'] : 'utf8mb4');
echo "Database: {$c['database']} (grup {$group}, {$cfgFile})\n";

$res = $my->query("SHOW COLUMNS FROM mstcoloader_company LIKE 'api_key_hash'");
if (!$res) {
    fail("Tabel mstcoloader_company belum ada di database ini. Jalankan database/001_booking_link.sql lalu 002_partner_key.sql.");
}
if ($res->num_rows === 0) {
    fail("Kolom api_key_hash belum ada. Jalankan database/002_partner_key.sql.");
}

function company_exists(mysqli $my, $code) {
    $st = $my->prepare('SELECT company_name FROM mstcoloader_company WHERE company_code = ?');
    $st->bind_param('s', $code); $st->execute(); $st->bind_result($name);
    $found = $st->fetch(); $st->close();
    return $found ? $name : null;
}
function store_hash(mysqli $my, $code, $hash) {
    $st = $my->prepare('UPDATE mstcoloader_company SET api_key_hash = ? WHERE company_code = ?');
    $st->bind_param('ss', $hash, $code);
    $ok = $st->execute(); $st->close();
    if (!$ok) fail('Gagal menyimpan: ' . $my->error);
}
function need_company(mysqli $my, $code) {
    if ($code === '' || company_exists($my, $code) === null) {
        fail("Kode partner '{$code}' tidak ada di mstcoloader_company. Jalankan: php partner_key.php status");
    }
}

switch ($cmd) {
    case 'status':
        $r = $my->query('SELECT company_code, company_name, is_active, api_key_hash FROM mstcoloader_company ORDER BY company_code');
        while ($row = $r->fetch_assoc()) {
            printf("%-4s %-45s aktif=%s key=%s\n", $row['company_code'], $row['company_name'],
                $row['is_active'] ? 'ya' : 'tidak', empty($row['api_key_hash']) ? 'BELUM' : 'sudah');
        }
        break;

    case 'generate':
        need_company($my, $code);
        $new = bin2hex(random_bytes(32));
        store_hash($my, $code, password_hash($new, PASSWORD_DEFAULT));
        echo "\nPartner key untuk {$code} (simpan sekarang, tidak bisa ditampilkan lagi):\n\n  {$new}\n\n";
        echo "Pasang di server {$code}, misalnya Osline: application/config/osline_secrets.php\n";
        echo "  'GATEWAY_PARTNER_KEY' => '{$new}',\n\n";
        break;

    case 'set':
        need_company($my, $code);
        if (strlen($key) < 32) fail('Key minimal 32 karakter. Gunakan "generate" untuk membuat key acak.');
        store_hash($my, $code, password_hash($key, PASSWORD_DEFAULT));
        echo "Key untuk {$code} disimpan.\n";
        break;

    case 'check':
        need_company($my, $code);
        $st = $my->prepare('SELECT api_key_hash FROM mstcoloader_company WHERE company_code = ?');
        $st->bind_param('s', $code); $st->execute(); $st->bind_result($hash); $st->fetch(); $st->close();
        if (empty($hash)) fail("Key untuk {$code} belum diset.");
        echo password_verify($key, $hash) ? "COCOK\n" : "TIDAK COCOK\n";
        break;

    case 'revoke':
        need_company($my, $code);
        $st = $my->prepare('UPDATE mstcoloader_company SET api_key_hash = NULL WHERE company_code = ?');
        $st->bind_param('s', $code); $st->execute(); $st->close();
        echo "Key untuk {$code} dicabut.\n";
        break;

    default:
        fail("Perintah '{$cmd}' tidak dikenal. Pakai: status | generate KODE | set KODE KEY | check KODE KEY | revoke KODE");
}
