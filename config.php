<?php
declare(strict_types=1);

define('BASE_URL', '/');

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__));
}
if (!defined('INC_DIR')) {
    define('INC_DIR', ROOT_DIR . '/inc');
}
if (!defined('SEC_DIR')) {
    define('SEC_DIR', ROOT_DIR . '/secure');
}

if (!function_exists('qanto_asset_seed')) {
    function qanto_asset_seed(): int
    {
        static $seed = null;
        if ($seed !== null) {
            return $seed;
        }

        $candidates = [
            __FILE__,
            ROOT_DIR . '/secure/index.php',
            ROOT_DIR . '/assets/css/secure.css',
        ];

        $seed = 0;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $seed = max($seed, (int)filemtime($candidate));
            }
        }

        return $seed > 0 ? $seed : time();
    }
}

if (!function_exists('asset_version')) {
    function asset_version(string $path): string
    {
        if (preg_match('~^https?://~i', $path) === 1) {
            $separator = str_contains($path, '?') ? '&' : '?';
            return $path . $separator . 'v=' . qanto_asset_seed();
        }

        $path = ltrim($path, '/');
        $file = __DIR__ . '/' . $path;
        $url = '/' . $path;

        return is_file($file) ? $url . '?v=' . (int)filemtime($file) : $url;
    }
}

if (!function_exists('is_local_environment')) {
    function is_local_environment(): bool
    {
        $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
        $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

        return str_contains($hostHeader, '.local')
            || in_array($remoteIp, ['127.0.0.1', '::1'], true)
            || in_array($serverAddr, ['127.0.0.1', '::1'], true);
    }
}

if (!function_exists('production_asset_url')) {
    function production_asset_url(string $path): string
    {
        return 'https://www.qanto.cz/' . ltrim($path, '/');
    }
}
