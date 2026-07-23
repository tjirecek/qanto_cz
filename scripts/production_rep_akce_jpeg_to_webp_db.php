<?php
declare(strict_types=1);

/**
 * Produkcni jednorazovy skript pro prepnuti DB referenci stran akci z JPG/JPEG na WebP.
 *
 * Skript neprevadi obrazky. Pouze najde existujici sourozene .webp soubory, overi je
 * a aktualizuje rep_akce_strany + pripadnou obalku rep_akce.cover_image.
 *
 * Pouziti:
 *   php scripts/production_rep_akce_jpeg_to_webp_db.php
 *   php scripts/production_rep_akce_jpeg_to_webp_db.php --run
 *   php scripts/production_rep_akce_jpeg_to_webp_db.php --run --delete-originals
 *   php scripts/production_rep_akce_jpeg_to_webp_db.php --run --offer=123
 *   php scripts/production_rep_akce_jpeg_to_webp_db.php --config=ini/config_local.ini
 *
 * HTTP rezim:
 *   1) do ini/config.ini docasne pridej rep_akce_web_token="nahodny-dlouhy-token"
 *   2) dry-run: /scripts/production_rep_akce_jpeg_to_webp_db.php?token=...&delete-originals=1
 *   3) zapis:   /scripts/production_rep_akce_jpeg_to_webp_db.php?token=...&run=1&delete-originals=1&confirm=PREPISAT_WEBP_A_SMAZAT_JPEG
 */

$rootDir = dirname(__DIR__);
date_default_timezone_set('Europe/Prague');

$isCli = PHP_SAPI === 'cli';
$httpRunDenied = false;
$args = $isCli ? $argv : http_args($httpRunDenied);
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$run = in_array('--run', $args, true);
$deleteOriginals = in_array('--delete-originals', $args, true);
$offerId = 0;
$limit = 0;
$configPath = $rootDir . '/ini/config.ini';

foreach ($args as $arg) {
    if (str_starts_with($arg, '--offer=')) {
        $offerId = max(0, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--config=')) {
        $path = trim(substr($arg, 9));
        $configPath = str_starts_with($path, '/') ? $path : $rootDir . '/' . ltrim($path, '/');
    }
}

if (!is_file($configPath)) {
    write_error("Konfiguracni soubor neexistuje: {$configPath}");
    exit(1);
}

$config = parse_ini_file($configPath, false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    write_error("Nelze nacist konfiguraci: {$configPath}");
    exit(1);
}

if (!$isCli) {
    $configuredToken = trim((string)($config['rep_akce_web_token'] ?? ''));
    $requestToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
    if ($configuredToken === '') {
        write_error('HTTP spusteni neni povolene. V ini/config.ini chybi rep_akce_web_token.');
        exit(1);
    }
    if ($requestToken === '' || !hash_equals($configuredToken, $requestToken)) {
        write_error('Neplatny token.');
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            echo 'Config token: len=' . strlen($configuredToken) . ', sha256=' . substr(hash('sha256', $configuredToken), 0, 12) . "\n";
            echo 'Request token: len=' . strlen($requestToken) . ', sha256=' . substr(hash('sha256', $requestToken), 0, 12) . "\n";
            echo 'Config path: ' . relative_path($rootDir, $configPath) . "\n";
        }
        exit(1);
    }
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$dbName = (string)($config['dbname'] ?? '');

if ($user === '' || $dbName === '') {
    write_error('V konfiguraci chybi user nebo dbname.');
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);

$sql = "SELECT s.id, s.akce_id, s.poradi, s.image_file, s.image_path, s.mime_type, a.cover_image
        FROM rep_akce_strany s
        INNER JOIN rep_akce a ON a.id = s.akce_id
        WHERE s.valid = 1
          AND (
              s.mime_type IN ('image/jpeg', 'image/jpg')
              OR s.image_file REGEXP '\\.jpe?g$'
              OR s.image_path REGEXP '\\.jpe?g$'
          )";
$params = [];
if ($offerId > 0) {
    $sql .= ' AND s.akce_id = :offer_id';
    $params[':offer_id'] = $offerId;
}
$sql .= ' ORDER BY s.akce_id ASC, s.poradi ASC, s.id ASC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

$updatePage = $pdo->prepare('UPDATE rep_akce_strany
    SET image_file = :image_file,
        image_path = :image_path,
        mime_type = :mime_type,
        width = :width,
        height = :height,
        filesize = :filesize,
        user_u = :user_u
    WHERE id = :id');
$updateCover = $pdo->prepare('UPDATE rep_akce
    SET cover_image = :cover_image,
        user_u = :user_u
    WHERE id = :id');

if (!$run) {
    echo "DRY RUN - pro zapis pouzij --run.\n";
}
if ($httpRunDenied) {
    echo "HTTP zapis byl odmitnut: pro ostry beh pridej confirm=PREPISAT_WEBP_A_SMAZAT_JPEG.\n";
}
if ($deleteOriginals && !$run) {
    echo "Mazani JPG/JPEG originalu je aktivni az pri --run --delete-originals.\n";
}
echo "Config: " . relative_path($rootDir, $configPath) . "\n";
echo "DB: {$dbName}@{$host}:{$port}\n";

$checked = 0;
$ready = 0;
$wouldDelete = 0;
$updated = 0;
$coversUpdated = 0;
$deleted = 0;
$deleteSkipped = 0;
$deleteErrors = 0;
$missingWebp = 0;
$invalidWebp = 0;
$withoutTarget = 0;
$errors = 0;
$affectedOffers = [];

foreach ($rows as $row) {
    $checked++;
    $sourceRelative = normalize_relative_path((string)($row['image_path'] ?? ''));
    $sourceFile = trim((string)($row['image_file'] ?? ''));
    $targetRelative = target_webp_path($sourceRelative, $sourceFile);

    if ($targetRelative === '') {
        $withoutTarget++;
        echo sprintf(
            "NOEXT page #%d offer #%d: %s\n",
            (int)$row['id'],
            (int)$row['akce_id'],
            $sourceRelative,
        );
        continue;
    }

    $targetAbsolute = $rootDir . '/' . $targetRelative;
    if (!is_file($targetAbsolute)) {
        $missingWebp++;
        echo sprintf(
            "MISS page #%d offer #%d: %s\n",
            (int)$row['id'],
            (int)$row['akce_id'],
            $targetRelative,
        );
        continue;
    }

    $imageInfo = @getimagesize($targetAbsolute);
    if ($imageInfo === false || strtolower((string)($imageInfo['mime'] ?? '')) !== 'image/webp') {
        $invalidWebp++;
        echo sprintf(
            "BAD page #%d offer #%d: %s\n",
            (int)$row['id'],
            (int)$row['akce_id'],
            $targetRelative,
        );
        continue;
    }

    $ready++;
    if (!$run) {
        if ($deleteOriginals && can_delete_original($rootDir, $sourceRelative, $targetRelative)) {
            $wouldDelete++;
        }
        continue;
    }

    try {
        $pdo->beginTransaction();

        $updatePage->execute([
            ':image_file' => basename($targetRelative),
            ':image_path' => $targetRelative,
            ':mime_type' => 'image/webp',
            ':width' => (int)($imageInfo[0] ?? 0),
            ':height' => (int)($imageInfo[1] ?? 0),
            ':filesize' => (int)filesize($targetAbsolute),
            ':user_u' => 'prod-webp-db',
            ':id' => (int)$row['id'],
        ]);

        if (normalize_relative_path((string)($row['cover_image'] ?? '')) === $sourceRelative) {
            $updateCover->execute([
                ':cover_image' => $targetRelative,
                ':user_u' => 'prod-webp-db',
                ':id' => (int)$row['akce_id'],
            ]);
            $coversUpdated++;
        }

        $pdo->commit();
        $updated++;
        $affectedOffers[(int)$row['akce_id']] = true;

        if ($deleteOriginals) {
            $deleteResult = delete_original($rootDir, $sourceRelative, $targetRelative);
            if ($deleteResult === 'deleted') {
                $deleted++;
            } elseif ($deleteResult === 'error') {
                $deleteErrors++;
                echo sprintf(
                    "DELERR page #%d offer #%d: %s\n",
                    (int)$row['id'],
                    (int)$row['akce_id'],
                    $sourceRelative,
                );
            } else {
                $deleteSkipped++;
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors++;
        echo sprintf(
            "ERR page #%d offer #%d: %s\n",
            (int)$row['id'],
            (int)$row['akce_id'],
            $e->getMessage(),
        );
    }
}

echo "\nSouhrn:\n";
echo "- zkontrolovano: {$checked}\n";
echo "- pripraveno k zapisu: {$ready}\n";
echo "- ke smazani v dry-run: {$wouldDelete}\n";
echo "- aktualizovane strany: {$updated}\n";
echo "- aktualizovane obalky: {$coversUpdated}\n";
echo "- smazane JPG/JPEG originaly: {$deleted}\n";
echo "- preskocene mazani: {$deleteSkipped}\n";
echo "- chyby mazani: {$deleteErrors}\n";
echo "- chybi WebP soubor: {$missingWebp}\n";
echo "- neplatny WebP soubor: {$invalidWebp}\n";
echo "- nelze odvodit cilovy WebP: {$withoutTarget}\n";
echo "- dotcene akce: " . count($affectedOffers) . "\n";
echo "- chyby: {$errors}\n";

function normalize_relative_path(string $path): string
{
    return ltrim(trim($path), '/');
}

function target_webp_path(string $sourceRelative, string $sourceFile): string
{
    if ($sourceRelative !== '' && preg_match('~\.jpe?g$~i', $sourceRelative) === 1) {
        return (string)preg_replace('~\.jpe?g$~i', '.webp', $sourceRelative);
    }

    if ($sourceFile !== '' && preg_match('~\.jpe?g$~i', $sourceFile) === 1) {
        $directory = trim(dirname($sourceRelative), '.');
        $targetFile = (string)preg_replace('~\.jpe?g$~i', '.webp', $sourceFile);
        return ($directory !== '' ? trim($directory, '/') . '/' : '') . $targetFile;
    }

    return '';
}

function relative_path(string $rootDir, string $path): string
{
    $rootDir = rtrim($rootDir, '/') . '/';
    return str_starts_with($path, $rootDir) ? substr($path, strlen($rootDir)) : $path;
}

function http_args(bool &$runDenied): array
{
    $args = ['http'];
    $runRequested = isset($_GET['run']) || isset($_POST['run']);
    $confirm = trim((string)($_GET['confirm'] ?? $_POST['confirm'] ?? ''));
    if ($runRequested && hash_equals('PREPISAT_WEBP_A_SMAZAT_JPEG', $confirm)) {
        $args[] = '--run';
    } elseif ($runRequested) {
        $runDenied = true;
    }

    if (isset($_GET['delete-originals']) || isset($_POST['delete-originals']) || isset($_GET['delete_originals']) || isset($_POST['delete_originals'])) {
        $args[] = '--delete-originals';
    }

    $offer = (int)($_GET['offer'] ?? $_POST['offer'] ?? 0);
    if ($offer > 0) {
        $args[] = '--offer=' . $offer;
    }

    $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 0);
    if ($limit > 0) {
        $args[] = '--limit=' . $limit;
    }

    return $args;
}

function write_error(string $message): void
{
    if (PHP_SAPI === 'cli' && defined('STDERR')) {
        fwrite(STDERR, $message . "\n");
        return;
    }

    echo $message . "\n";
}

function can_delete_original(string $rootDir, string $sourceRelative, string $targetRelative): bool
{
    if ($sourceRelative === '' || $sourceRelative === $targetRelative) {
        return false;
    }
    if (preg_match('~\.jpe?g$~i', $sourceRelative) !== 1) {
        return false;
    }

    $projectRoot = realpath($rootDir);
    $sourcePath = realpath($rootDir . '/' . $sourceRelative);
    if ($projectRoot === false || $sourcePath === false || !is_file($sourcePath)) {
        return false;
    }

    return str_starts_with($sourcePath, rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

function delete_original(string $rootDir, string $sourceRelative, string $targetRelative): string
{
    if (!can_delete_original($rootDir, $sourceRelative, $targetRelative)) {
        return 'skipped';
    }

    $sourcePath = realpath($rootDir . '/' . $sourceRelative);
    if ($sourcePath === false || !@unlink($sourcePath)) {
        return 'error';
    }

    return 'deleted';
}
