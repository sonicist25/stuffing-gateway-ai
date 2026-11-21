<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared Redis cache untuk aplikasi GCL/CI3.
 *
 * Prinsip:
 * - phpredis native extension
 * - fail-open: Redis error tidak boleh membuat aplikasi gagal
 * - JSON serialization, optional gzip
 * - tidak cache auth/session; hanya business data yang sudah lolos authorization
 */
class Gcl_redis_cache
{
    /** @var CI_Controller */
    protected $CI;

    /** @var Redis|null */
    protected $redis = null;

    protected $cfg = array();
    protected $available = null;
    protected $lastError = null;

    const GZIP_MAGIC = "GCLZ1:";
    const JSON_MAGIC = "GCLJ1:";

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->config->load('gcl_redis', true);
        $cfg = $this->CI->config->item('gcl_redis');

        if (is_array($cfg) && isset($cfg['gcl_redis']) && is_array($cfg['gcl_redis'])) {
            $cfg = $cfg['gcl_redis'];
        }

        $this->cfg = is_array($cfg) ? $cfg : array();
    }

    public function isAvailable()
    {
        if ($this->available !== null) {
            return $this->available;
        }

        if (!$this->cfgValue('enabled', true)) {
            $this->available = false;
            $this->lastError = 'Redis disabled by config.';
            return false;
        }

        if (!class_exists('Redis')) {
            $this->available = false;
            $this->lastError = 'PHP Redis extension (phpredis) not installed.';
            return false;
        }

        try {
            $redis = new Redis();
            $connected = $redis->connect(
                (string) $this->cfgValue('host', '127.0.0.1'),
                (int) $this->cfgValue('port', 6379),
                (float) $this->cfgValue('connect_timeout', 1.0)
            );

            if (!$connected) {
                throw new RuntimeException('Redis connect returned false.');
            }

            if (defined('Redis::OPT_READ_TIMEOUT')) {
                $redis->setOption(
                    Redis::OPT_READ_TIMEOUT,
                    (float) $this->cfgValue('read_timeout', 1.5)
                );
            }

            $password = (string) $this->cfgValue('password', '');
            if ($password !== '' && !$redis->auth($password)) {
                throw new RuntimeException('Redis authentication failed.');
            }

            $database = (int) $this->cfgValue('database', 0);
            if ($database > 0 && !$redis->select($database)) {
                throw new RuntimeException('Redis SELECT database failed.');
            }

            // Ping hanya untuk memastikan koneksi hidup.
            $redis->ping();

            $this->redis = $redis;
            $this->available = true;
            $this->lastError = null;
            return true;
        } catch (Throwable $e) {
            $this->available = false;
            $this->redis = null;
            $this->lastError = $e->getMessage();
            log_message('error', 'GCL Redis unavailable: '.$e->getMessage());
            return false;
        }
    }

    public function get($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $raw = $this->redis->get($this->fullKey($key));

            if ($raw === false || $raw === null || $raw === '') {
                return null;
            }

            return $this->decode($raw);
        } catch (Throwable $e) {
            $this->markFailure($e);
            return null;
        }
    }

    public function set($key, $value, $ttlSeconds)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $ttlSeconds = max(1, (int) $ttlSeconds);

        try {
            $payload = $this->encode($value);
            return (bool) $this->redis->setex(
                $this->fullKey($key),
                $ttlSeconds,
                $payload
            );
        } catch (Throwable $e) {
            $this->markFailure($e);
            return false;
        }
    }

    public function delete($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->redis->del($this->fullKey($key)) >= 0;
        } catch (Throwable $e) {
            $this->markFailure($e);
            return false;
        }
    }

    /**
     * Hapus key berdasarkan prefix menggunakan SCAN, bukan KEYS.
     * Contoh: deleteByPrefix('booking:list:v1:shipper:4407:')
     */
    public function deleteByPrefix($prefix)
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        $pattern = $this->fullKey($prefix).'*';
        $deleted = 0;
        $iterator = null;

        try {
            do {
                $keys = $this->redis->scan($iterator, $pattern, 200);

                if ($keys === false) {
                    continue;
                }

                if (!empty($keys)) {
                    $deleted += (int) $this->redis->del($keys);
                }
            } while ($iterator > 0);

            return $deleted;
        } catch (Throwable $e) {
            $this->markFailure($e);
            return $deleted;
        }
    }

    public function ttl($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            return (int) $this->redis->ttl($this->fullKey($key));
        } catch (Throwable $e) {
            $this->markFailure($e);
            return null;
        }
    }

    public function configValue($key, $default = null)
    {
        return $this->cfgValue($key, $default);
    }

    public function status()
    {
        $ok = $this->isAvailable();

        return array(
            'enabled' => (bool) $this->cfgValue('enabled', true),
            'available' => $ok,
            'host' => (string) $this->cfgValue('host', '127.0.0.1'),
            'port' => (int) $this->cfgValue('port', 6379),
            'database' => (int) $this->cfgValue('database', 0),
            'prefix' => (string) $this->cfgValue('prefix', 'gcl:'),
            'last_error' => $this->lastError,
        );
    }

    protected function encode($value)
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException('Redis JSON encode failed: '.json_last_error_msg());
        }

        $compress = (bool) $this->cfgValue('compress', true);
        $minBytes = (int) $this->cfgValue('compress_min_bytes', 2048);

        if ($compress && strlen($json) >= $minBytes && function_exists('gzencode')) {
            $gz = gzencode($json, 6);
            if ($gz !== false) {
                return self::GZIP_MAGIC.$gz;
            }
        }

        return self::JSON_MAGIC.$json;
    }

    protected function decode($raw)
    {
        if (strncmp($raw, self::GZIP_MAGIC, strlen(self::GZIP_MAGIC)) === 0) {
            $compressed = substr($raw, strlen(self::GZIP_MAGIC));
            $json = function_exists('gzdecode') ? gzdecode($compressed) : false;

            if ($json === false) {
                return null;
            }
        } elseif (strncmp($raw, self::JSON_MAGIC, strlen(self::JSON_MAGIC)) === 0) {
            $json = substr($raw, strlen(self::JSON_MAGIC));
        } else {
            // backward-compatible bila pernah menyimpan JSON plain.
            $json = $raw;
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function fullKey($key)
    {
        return (string) $this->cfgValue('prefix', 'gcl:').ltrim((string) $key, ':');
    }

    protected function cfgValue($key, $default = null)
    {
        return array_key_exists($key, $this->cfg)
            ? $this->cfg[$key]
            : $default;
    }

    protected function markFailure(Throwable $e)
    {
        $this->lastError = $e->getMessage();
        $this->available = false;
        $this->redis = null;
        log_message('error', 'GCL Redis runtime error: '.$e->getMessage());
    }
}
