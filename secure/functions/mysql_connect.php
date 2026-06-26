<?php
declare(strict_types=1);

// DB connector by neměl startovat session
// session_start() řeš v index.php / auth.php apod.

if (!defined('ROOT_DIR')) {
    // fallback, kdyby někdo mysql_connect includnul bez config.php
    define('ROOT_DIR', realpath(dirname(__DIR__)));
}
if (!defined('INC_DIR')) {
    define('INC_DIR', ROOT_DIR . '/inc');
}
if (!defined('SEC_DIR')) {
    define('SEC_DIR', ROOT_DIR . '/secure');
}

/** Detekce prostředí: lokál = *.local / 127.0.0.1 / ::1 */
$hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
$remoteIp   = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

$isLocal = (
    str_contains($hostHeader, '.local')
    || in_array($remoteIp, ['127.0.0.1', '::1'], true)
    || in_array($serverAddr, ['127.0.0.1', '::1'], true)
);

/** INI soubor */
$iniPath = $isLocal
    ? ROOT_DIR . '/ini/config_local.ini'
    : ROOT_DIR . '/ini/config.ini';

if (!is_file($iniPath)) {
    throw new RuntimeException("INI soubor nenalezen: $iniPath");
}

$config = parse_ini_file($iniPath, false, INI_SCANNER_TYPED);
if ($config === false) {
    throw new RuntimeException("Nelze načíst INI soubor: $iniPath");
}

/** DB konfigurace */
$host    = (string)($config['host'] ?? '127.0.0.1');
$port    = (int)($config['port'] ?? 3306);
$db      = (string)($config['dbname'] ?? '');
$user    = (string)($config['user'] ?? '');
$pass    = (string)($config['password'] ?? '');
$charset = 'utf8mb4';

if ($db === '' || $user === '') {
    throw new RuntimeException("Chybí dbname/user v INI souboru: $iniPath");
}

/** PDO připojení */
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new RuntimeException('Chyba připojení k DB: ' . $e->getMessage(), (int)$e->getCode());
}