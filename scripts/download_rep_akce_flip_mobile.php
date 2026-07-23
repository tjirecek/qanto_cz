<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
define('ROOT_DIR', $rootDir);
define('BASE_URL', '/');

require_once $rootDir . '/secure/functions/fun_rep_akce.php';

date_default_timezone_set('Europe/Prague');

$options = [
    'run' => in_array('--run', $argv, true),
    'force' => in_array('--force', $argv, true),
    'import' => in_array('--import-to-media', $argv, true),
    'replace' => !in_array('--no-replace', $argv, true),
    'base_url' => 'https://www.qanto.cz/_files/_flip',
    'offer_id' => 0,
    'limit' => 0,
    'timeout' => 30,
    'retries' => 2,
];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) {
        $options['base_url'] = rtrim(substr($arg, 11), '/');
    } elseif (str_starts_with($arg, '--offer=')) {
        $options['offer_id'] = max(0, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--limit=')) {
        $options['limit'] = max(0, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--timeout=')) {
        $options['timeout'] = max(5, (int)substr($arg, 10));
    } elseif (str_starts_with($arg, '--retries=')) {
        $options['retries'] = max(0, (int)substr($arg, 10));
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
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);

function rep_akce_flip_download_mkdir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Nelze vytvorit adresar: ' . $dir);
    }
}

function rep_akce_flip_download_url(string $url, string $target, bool $force, int $timeout, int $retries): string
{
    if (!$force && is_file($target) && filesize($target) > 0) {
        return 'skipped';
    }

    rep_akce_flip_download_mkdir(dirname($target));
    $tmp = $target . '.part';
    $attempts = $retries + 1;
    $lastError = '';

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        $fp = fopen($tmp, 'wb');
        if (!$fp) {
            throw new RuntimeException('Nelze otevrit docasny soubor: ' . $tmp);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FAILONERROR => false,
            CURLOPT_USERAGENT => 'qanto-akce-flip-mobile-downloader/1.0',
        ]);
        $ok = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($ok && $httpCode >= 200 && $httpCode < 300 && is_file($tmp) && filesize($tmp) > 0) {
            rename($tmp, $target);
            return 'downloaded';
        }

        @unlink($tmp);
        if ($httpCode === 404 || $httpCode === 403) {
            return 'missing';
        }
        $lastError = $error !== '' ? $error : 'HTTP ' . $httpCode;
        usleep(200000);
    }

    throw new RuntimeException($lastError !== '' ? $lastError : 'Stazeni selhalo');
}

function rep_akce_flip_page_count_from_config(string $configPath): int
{
    if (!is_file($configPath)) {
        return 0;
    }
    $config = file_get_contents($configPath);
    if (!is_string($config)) {
        return 0;
    }
    if (preg_match('~bookConfig\.totalPageCount\s*=\s*(\d+)~', $config, $match) === 1) {
        return (int)$match[1];
    }
    if (preg_match('~totalPageCount\s*:\s*(\d+)~', $config, $match) === 1) {
        return (int)$match[1];
    }
    return 0;
}

$sql = "SELECT id, legacy_id, nazev_cz, legacy_flip, legacy_flip_path
        FROM rep_akce
        WHERE legacy_flip <> ''";
$params = [];
if ($options['offer_id'] > 0) {
    $sql .= ' AND id = :id';
    $params[':id'] = $options['offer_id'];
}
$sql .= ' ORDER BY id ASC';
if ($options['limit'] > 0) {
    $sql .= ' LIMIT ' . (int)$options['limit'];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll() ?: [];

if (!$options['run']) {
    echo "DRY RUN - pro zapis pouzij --run.\n";
}

echo 'Base URL: ' . $options['base_url'] . "\n";

$checked = 0;
$configDownloaded = 0;
$configSkipped = 0;
$configMissing = 0;
$pageDownloaded = 0;
$pageSkipped = 0;
$pageMissing = 0;
$importedOffers = 0;
$importedPages = 0;
$errors = 0;

foreach ($offers as $offer) {
    $checked++;
    $flip = trim((string)($offer['legacy_flip'] ?? ''), "/ \t\n\r\0\x0B");
    if ($flip === '') {
        continue;
    }

    $localRoot = $rootDir . '/_files/akce_old/_flip/' . $flip;
    $configTarget = $localRoot . '/mobile/javascript/config.js';
    $configUrl = $options['base_url'] . '/' . rawurlencode($flip) . '/mobile/javascript/config.js';

    try {
        if ($options['run']) {
            $status = rep_akce_flip_download_url($configUrl, $configTarget, (bool)$options['force'], (int)$options['timeout'], (int)$options['retries']);
            if ($status === 'downloaded') {
                $configDownloaded++;
            } elseif ($status === 'skipped') {
                $configSkipped++;
            } elseif ($status === 'missing') {
                $configMissing++;
                echo sprintf("MISS #%d %s: config.js neni dostupny\n", (int)$offer['id'], $flip);
                continue;
            }
        } elseif (!is_file($configTarget)) {
            echo sprintf("PLAN #%d %s: stahnout config + zjistit pocet stran\n", (int)$offer['id'], $flip);
            continue;
        }

        $pageCount = rep_akce_flip_page_count_from_config($configTarget);
        if ($pageCount <= 0) {
            $errors++;
            echo sprintf("ERR  #%d %s: v config.js neni totalPageCount\n", (int)$offer['id'], $flip);
            continue;
        }

        $localPages = glob($localRoot . '/files/mobile/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        $localPageCountBefore = count($localPages);
        echo sprintf("%s #%d %s: %d stran%s\n", $options['run'] ? 'LOAD' : 'PLAN', (int)$offer['id'], $flip, $pageCount, $localPageCountBefore > 0 ? ', lokalne ' . $localPageCountBefore : '');

        if ($options['run']) {
            for ($page = 1; $page <= $pageCount; $page++) {
                $pageTarget = $localRoot . '/files/mobile/' . $page . '.jpg';
                $pageUrl = $options['base_url'] . '/' . rawurlencode($flip) . '/files/mobile/' . $page . '.jpg';
                $status = rep_akce_flip_download_url($pageUrl, $pageTarget, (bool)$options['force'], (int)$options['timeout'], (int)$options['retries']);
                if ($status === 'downloaded') {
                    $pageDownloaded++;
                } elseif ($status === 'skipped') {
                    $pageSkipped++;
                } elseif ($status === 'missing') {
                    $pageMissing++;
                    echo sprintf("MISS #%d %s: strana %d\n", (int)$offer['id'], $flip, $page);
                }
            }

            if ($options['import']) {
                $pdo->beginTransaction();
                try {
                    $result = rep_akce_import_flip_pages($pdo, (int)$offer['id'], (bool)$options['replace']);
                    $pdo->commit();
                    $importedOffers++;
                    $importedPages += (int)$result['imported'];
                    echo sprintf("DONE #%d %s: prevedeno do media %d stran\n", (int)$offer['id'], $flip, (int)$result['imported']);
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
            }
        }
    } catch (Throwable $e) {
        $errors++;
        echo sprintf("ERR  #%d %s: %s\n", (int)$offer['id'], $flip, $e->getMessage());
    }
}

echo "\nSouhrn:\n";
echo "- zkontrolovano akci: {$checked}\n";
echo "- config stazeno/preskoceno/chybi: {$configDownloaded}/{$configSkipped}/{$configMissing}\n";
echo "- strany stazeno/preskoceno/chybi: {$pageDownloaded}/{$pageSkipped}/{$pageMissing}\n";
echo "- prevedeno do media akci/stran: {$importedOffers}/{$importedPages}\n";
echo "- chyby: {$errors}\n";
