<?php
// Vision HR - Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'erp_hr3');
if (!defined('DB_USER')) define('DB_USER', 'hrweb');
if (!defined('DB_PASS')) define('DB_PASS', 'HrWeb#2026!Fix');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Site settings
if (!defined('SITE_URL')) define('SITE_URL', 'https://hr.gt-academy.com/');
if (!defined('SITE_TITLE')) define('SITE_TITLE', 'Vision HR');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', __DIR__ . '/../uploads/');

// Timezone
date_default_timezone_set('Asia/Riyadh');

// Force UTF-8 everywhere
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=utf-8');

// PDO Connection
try {
    $connect_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Global date variables used throughout the system
$today_date = date('Y-m-d');
$now_date = date('Y-m-d H:i:s');

// Date format for display
$dateformat = 'Y-m-d';
$datetimeformat = 'Y-m-d H:i:s';

if (!function_exists('perf_cache_can_use_apcu')) {
    function perf_cache_can_use_apcu(): bool
    {
        if (!function_exists('apcu_fetch') || !function_exists('apcu_store') || !function_exists('apcu_delete')) {
            return false;
        }

        if (!filter_var((string)ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (PHP_SAPI === 'cli' && !filter_var((string)ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('perf_cache_directory')) {
    function perf_cache_directory(): string
    {
        static $directory = null;
        if ($directory !== null) {
            return $directory;
        }

        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $directory = $base . DIRECTORY_SEPARATOR . 'vision_hr_runtime_cache';

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory;
    }
}

if (!function_exists('perf_cache_key')) {
    function perf_cache_key(string $namespace, array $parts = []): string
    {
        if (!$parts) {
            return $namespace;
        }

        return $namespace . ':' . sha1(json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('perf_cache_file_path')) {
    function perf_cache_file_path(string $key): string
    {
        return perf_cache_directory() . DIRECTORY_SEPARATOR . sha1($key) . '.cache.php';
    }
}

if (!function_exists('perf_cache_get')) {
    function perf_cache_get(string $key, $default = null)
    {
        if (perf_cache_can_use_apcu()) {
            $success = false;
            $value = apcu_fetch($key, $success);
            if ($success) {
                return $value;
            }
        }

        $path = perf_cache_file_path($key);
        if (!is_file($path)) {
            return $default;
        }

        $payload = @unserialize((string)@file_get_contents($path));
        if (!is_array($payload) || !array_key_exists('expires_at', $payload)) {
            @unlink($path);
            return $default;
        }

        if ((int)$payload['expires_at'] < time()) {
            @unlink($path);
            return $default;
        }

        return $payload['value'] ?? $default;
    }
}

if (!function_exists('perf_cache_set')) {
    function perf_cache_set(string $key, $value, int $ttl = 60): bool
    {
        $ttl = max(1, $ttl);

        if (perf_cache_can_use_apcu()) {
            @apcu_store($key, $value, $ttl);
        }

        $payload = serialize([
            'expires_at' => time() + $ttl,
            'value' => $value,
        ]);

        return @file_put_contents(perf_cache_file_path($key), $payload, LOCK_EX) !== false;
    }
}

if (!function_exists('perf_cache_delete')) {
    function perf_cache_delete(string $key): void
    {
        if (perf_cache_can_use_apcu()) {
            @apcu_delete($key);
        }

        $path = perf_cache_file_path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('perf_cache_remember')) {
    function perf_cache_remember(string $key, int $ttl, callable $resolver)
    {
        $cached = perf_cache_get($key, null);
        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();
        perf_cache_set($key, $value, $ttl);
        return $value;
    }
}
