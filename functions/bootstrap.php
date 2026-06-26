<?php
declare(strict_types=1);

/**
 * Jednotný bootstrap pro celý web (web + /secure + AJAX)
 * - project_session_name z INI
 * - admin session namespace z INI
 * - cookie params (path=/ => sdílené)
 * - bezpečnější defaults
 */

function app_is_https(): bool
{
    return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

function app_is_local_environment(): bool
{
    $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

    return str_contains($hostHeader, '.local')
        || in_array($remoteIp, ['127.0.0.1', '::1'], true)
        || in_array($serverAddr, ['127.0.0.1', '::1'], true);
}

function app_bootstrap_config(): array
{
    static $bootstrapConfig = null;
    if (is_array($bootstrapConfig)) {
        return $bootstrapConfig;
    }

    $rootDir = dirname(__DIR__);
    $iniPath = app_is_local_environment()
        ? $rootDir . '/ini/config_local.ini'
        : $rootDir . '/ini/config.ini';

    $bootstrapConfig = is_file($iniPath)
        ? (parse_ini_file($iniPath, false, INI_SCANNER_TYPED) ?: [])
        : [];

    return $bootstrapConfig;
}

function app_session_name(): string
{
    $config = app_bootstrap_config();
    $name = trim((string)($config['project_session_name'] ?? $config['admin_session_name'] ?? 'APPSESSID'));

    return preg_match('~^[A-Za-z0-9_,-]{1,128}$~', $name) ? $name : 'APPSESSID';
}

function admin_session_namespace(): string
{
    $config = app_bootstrap_config();
    $namespace = trim((string)($config['admin_session_namespace'] ?? 'admin'));

    return preg_match('~^[A-Za-z0-9_]{1,80}$~', $namespace) ? $namespace : 'admin';
}

function admin_session_all(): array
{
    $namespace = admin_session_namespace();
    $data = $_SESSION[$namespace] ?? [];

    return is_array($data) ? $data : [];
}

function admin_session_get(string $key, mixed $default = null): mixed
{
    $namespace = admin_session_namespace();

    return $_SESSION[$namespace][$key] ?? $default;
}

function admin_session_set(string $key, mixed $value): void
{
    $namespace = admin_session_namespace();
    if (!isset($_SESSION[$namespace]) || !is_array($_SESSION[$namespace])) {
        $_SESSION[$namespace] = [];
    }

    $_SESSION[$namespace][$key] = $value;
}

function admin_session_unset(string $key): void
{
    $namespace = admin_session_namespace();
    unset($_SESSION[$namespace][$key]);
}

function admin_session_clear(): void
{
    unset($_SESSION[admin_session_namespace()]);
}

function admin_session_user(): string
{
    return (string)admin_session_get('user', '');
}

function admin_session_user_name(): string
{
    $name = (string)admin_session_get('user_name', '');
    return $name !== '' ? $name : admin_session_user();
}

function admin_session_prava(): int
{
    return (int)admin_session_get('user_prava', 0);
}

function admin_session_is_logged(): bool
{
    return (int)admin_session_get('logged', 0) === 1
        && (int)admin_session_get('is_admin', 0) === 1;
}

if (session_status() !== PHP_SESSION_ACTIVE) {

    $isHttps = app_is_https();

    session_name(app_session_name());

    // cookie pravidla musí být nastavená PŘED session_start()
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',      // sdílení i pro /secure
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // baseline ochrana (neřeší login/logout - jen obecně)
    $initKey = '_' . admin_session_namespace() . '_init';
    if (!isset($_SESSION[$initKey])) {
        $_SESSION[$initKey] = 1;
        session_regenerate_id(true);
    }
}
