<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
define('ROOT_DIR', $rootDir);
define('BASE_URL', '/');

require_once $rootDir . '/secure/functions/fun_rep_akce.php';

date_default_timezone_set('Europe/Prague');

$run = in_array('--run', $argv, true);
$replace = in_array('--replace', $argv, true);
$offerId = 0;
$limit = 0;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--offer=')) {
        $offerId = max(0, (int)substr($arg, 8));
    }
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int)substr($arg, 8));
    }
}

$config = parse_ini_file($rootDir . '/ini/config_local.ini', false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist ini/config_local.ini\n");
    exit(1);
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$dbName = (string)($config['dbname'] ?? '');
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $password, $options);

$sql = "SELECT id, legacy_id, nazev_cz, legacy_flip, legacy_flip_path, cover_image FROM rep_akce WHERE legacy_flip <> '' OR legacy_flip_path <> ''";
$params = [];
if ($offerId > 0) {
    $sql .= ' AND id = :id';
    $params[':id'] = $offerId;
}
$sql .= ' ORDER BY id ASC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll() ?: [];

$checked = 0;
$available = 0;
$converted = 0;
$skippedExisting = 0;
$missing = 0;
$errors = 0;

if (!$run) {
    echo "DRY RUN - pro zapis pouzij --run. Pro nahrazeni existujicich stran pridej --replace.\n";
}

foreach ($offers as $offer) {
    $checked++;
    $mobileDir = rep_akce_flip_mobile_dir($offer);
    if ($mobileDir === '') {
        $missing++;
        echo sprintf("MISS #%d legacy #%s: _flip/files/mobile neni dostupne\n", (int)$offer['id'], (string)($offer['legacy_id'] ?? ''));
        continue;
    }
    $files = glob($mobileDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    natsort($files);
    $pageCount = count($files);
    $existing = rep_akce_page_count($pdo, (int)$offer['id']);
    $available++;

    if ($existing > 0 && !$replace) {
        $skippedExisting++;
        echo sprintf("SKIP #%d: existuje %d stran, _flip ma %d stran (%s)\n", (int)$offer['id'], $existing, $pageCount, (string)$offer['nazev_cz']);
        continue;
    }

    if (!$run) {
        echo sprintf("OK   #%d: pripraveno %d stran%s (%s)\n", (int)$offer['id'], $pageCount, $existing > 0 ? ', nahradi ' . $existing : '', (string)$offer['nazev_cz']);
        continue;
    }

    try {
        $pdo->beginTransaction();
        $result = rep_akce_import_flip_pages($pdo, (int)$offer['id'], $replace);
        $pdo->commit();
        $converted++;
        echo sprintf("DONE #%d: importovano %d stran (%s)\n", (int)$offer['id'], (int)$result['imported'], (string)$offer['nazev_cz']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors++;
        echo sprintf("ERR  #%d: %s\n", (int)$offer['id'], $e->getMessage());
    }
}

echo "\nSouhrn:\n";
echo "- zkontrolovano: {$checked}\n";
echo "- dostupny _flip/files/mobile: {$available}\n";
echo "- chybi _flip/files/mobile: {$missing}\n";
echo "- preskoceno kvuli existujicim strankam: {$skippedExisting}\n";
echo "- prevedeno: {$converted}\n";
echo "- chyby: {$errors}\n";
