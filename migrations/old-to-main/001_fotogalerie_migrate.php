<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');
$oldDbName = 'xqanto_cz_old';
$downloadedMediaRoot = $rootDir . '/media/galerie';
$legacyOldMediaRoot = '/Users/tjirecek/www_dev/old-qanto_cz/_images/_galerie';
$sourceMediaRoots = array_values(array_filter([$downloadedMediaRoot, $legacyOldMediaRoot], static fn (string $dir): bool => is_dir($dir)));
$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/001_fotogalerie_migrate_' . date('Ymd_His') . '.md';
$reset = in_array('--reset', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

if ($sourceMediaRoots === []) {
    fwrite(STDERR, "Zdrojove adresare neexistuji: {$downloadedMediaRoot}, {$legacyOldMediaRoot}\n");
    exit(1);
}

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Nelze vytvorit report adresar: {$reportDir}\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'qanto.local';

define('ROOT_DIR', $rootDir);
define('SEC_DIR', ROOT_DIR . '/secure');

require_once ROOT_DIR . '/functions/bootstrap.php';
require_once SEC_DIR . '/functions/fun_default.php';
require_once SEC_DIR . '/functions/fun_galerie.php';

$configPath = ROOT_DIR . '/ini/config_local.ini';
$config = parse_ini_file($configPath, false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist {$configPath}\n");
    exit(1);
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$targetDbName = (string)($config['dbname'] ?? '');
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$target = new PDO("mysql:host={$host};port={$port};dbname={$targetDbName};charset=utf8mb4", $user, $password, $options);
$old = new PDO("mysql:host={$host};port={$port};dbname={$oldDbName};charset=utf8mb4", $user, $password, $options);
$pdo = $target;

function migrate_count(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function migrate_table_nonempty(PDO $pdo): bool
{
    return migrate_count($pdo, 'galerie_typ') > 0
        || migrate_count($pdo, 'galerie') > 0
        || migrate_count($pdo, 'galerie_photo') > 0;
}

function migrate_text(mixed $value): string
{
    return (string)($value ?? '');
}

function migrate_nullable_type(PDO $target, mixed $value): ?int
{
    $id = (int)($value ?? 0);
    if ($id <= 0) {
        return null;
    }

    static $valid = null;
    if ($valid === null) {
        $valid = array_flip(array_map('intval', $target->query('SELECT id FROM galerie_typ')->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }

    return isset($valid[$id]) ? $id : null;
}

function migrate_same_realpath(string $pathA, string $pathB): bool
{
    $realA = realpath($pathA);
    $realB = realpath($pathB);

    return $realA !== false && $realB !== false && $realA === $realB;
}

function migrate_source_photo_path(array $sourceMediaRoots, int $galleryId, string $file): ?string
{
    foreach ($sourceMediaRoots as $sourceMediaRoot) {
        $candidate = rtrim($sourceMediaRoot, '/') . '/' . $galleryId . '-galerie/' . basename($file);
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function migrate_clear_generated_small_dirs(string $mediaRoot): void
{
    foreach (glob(rtrim($mediaRoot, '/') . '/*-galerie/small', GLOB_ONLYDIR) ?: [] as $smallDir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($smallDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($smallDir);
    }
}

function migrate_clear_target_media_root(string $mediaRoot): void
{
    if (!is_dir($mediaRoot)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($mediaRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
}

$sourceUsesTargetMedia = in_array(true, array_map(
    static fn (string $sourceMediaRoot): bool => migrate_same_realpath($sourceMediaRoot, galerie_media_root()),
    $sourceMediaRoots
), true);

$targetCountsBefore = [
    'galerie_typ' => migrate_count($target, 'galerie_typ'),
    'galerie' => migrate_count($target, 'galerie'),
    'galerie_photo' => migrate_count($target, 'galerie_photo'),
];

if (migrate_table_nonempty($target) && !$reset && !$dryRun) {
    fwrite(STDERR, "Cilove tabulky nejsou prazdne. Pouzij --reset, pokud je chces smazat a nahradit migraci.\n");
    fwrite(STDERR, json_encode($targetCountsBefore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}

if ($dryRun) {
    echo "DRY RUN - bez zapisu.\n";
}

$oldCounts = [
    'galerie_typ' => migrate_count($old, 'galerie_typ'),
    'galerie' => migrate_count($old, 'galerie'),
    'galerie_photo' => migrate_count($old, 'galerie_photo'),
];

$missingFiles = [];
$imageErrors = [];
$duplicatePhotos = [];
$copiedPhotos = 0;
$insertedPhotos = 0;
$skippedPhotos = 0;
$processedByGallery = [];
$seenTargetFiles = [];

if (!$dryRun) {
    $target->exec('SET FOREIGN_KEY_CHECKS = 0');
    if ($reset) {
        $target->exec('TRUNCATE TABLE galerie_photo');
        $target->exec('TRUNCATE TABLE galerie');
        $target->exec('TRUNCATE TABLE galerie_typ');

        if ($sourceUsesTargetMedia) {
            migrate_clear_generated_small_dirs(galerie_media_root());
        } else {
            migrate_clear_target_media_root(galerie_media_root());
        }
    }

    $target->beginTransaction();
}

try {
    $typeInsert = $target->prepare('INSERT INTO galerie_typ
        (id, poradi, nazev_cz, nazev_en, popis_cz, popis_en, valid, user_i, user_u)
        VALUES (:id, :poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :valid, :user_i, :user_u)');

    foreach ($old->query('SELECT * FROM galerie_typ ORDER BY ID')->fetchAll() ?: [] as $row) {
        if ($dryRun) {
            continue;
        }

        $typeInsert->execute([
            ':id' => (int)$row['ID'],
            ':poradi' => (int)($row['poradi'] ?? 0),
            ':nazev_cz' => migrate_text($row['nazev_cz'] ?? ''),
            ':nazev_en' => migrate_text($row['nazev_en'] ?? ''),
            ':popis_cz' => migrate_text($row['popis_cz'] ?? ''),
            ':popis_en' => migrate_text($row['popis_en'] ?? ''),
            ':valid' => (int)($row['valid'] ?? 1),
            ':user_i' => 'migration_old',
            ':user_u' => 'migration_old',
        ]);
    }

    $galleryInsert = $target->prepare('INSERT INTO galerie
        (id, nazev_cz, nazev_en, datum, galerie_typ, popis_cz, popis_en, valid, user_i, user_u)
        VALUES (:id, :nazev_cz, :nazev_en, :datum, :galerie_typ, :popis_cz, :popis_en, :valid, :user_i, :user_u)');

    foreach ($old->query('SELECT * FROM galerie ORDER BY ID')->fetchAll() ?: [] as $row) {
        $galleryId = (int)$row['ID'];
        if (!$dryRun) {
            $galleryInsert->execute([
                ':id' => $galleryId,
                ':nazev_cz' => migrate_text($row['nazev_cz'] ?? ''),
                ':nazev_en' => migrate_text($row['nazev_en'] ?? ''),
                ':datum' => ($row['datum'] ?? null) ?: null,
                ':galerie_typ' => migrate_nullable_type($target, $row['galerie_typ'] ?? null),
                ':popis_cz' => migrate_text($row['popis_cz'] ?? ''),
                ':popis_en' => migrate_text($row['popis_en'] ?? ''),
                ':valid' => (int)($row['valid'] ?? 1),
                ':user_i' => 'migration_old',
                ':user_u' => 'migration_old',
            ]);
            galerie_ensure_directories($galleryId);
        }
    }

    $photoInsert = $target->prepare('INSERT INTO galerie_photo
        (id, poradi, galerie_id, nazev_cz, nazev_en, soubor, mime_type, width, height, filesize, valid, user_i, user_u)
        VALUES (:id, :poradi, :galerie_id, :nazev_cz, :nazev_en, :soubor, :mime_type, :width, :height, :filesize, :valid, :user_i, :user_u)');

    $photos = $old->query('SELECT * FROM galerie_photo ORDER BY galerie_id, poradi, ID')->fetchAll() ?: [];
    foreach ($photos as $row) {
        $photoId = (int)$row['ID'];
        $galleryId = (int)$row['galerie_id'];
        $file = basename(migrate_text($row['soubor'] ?? ''));
        $fileKey = $galleryId . '/' . $file;

        if ($file !== '' && isset($seenTargetFiles[$fileKey])) {
            $duplicatePhotos[] = [
                'photo_id' => $photoId,
                'gallery_id' => $galleryId,
                'file' => $file,
                'kept_photo_id' => $seenTargetFiles[$fileKey],
            ];
            $skippedPhotos++;
            continue;
        }

        $sourcePath = $file !== '' ? migrate_source_photo_path($sourceMediaRoots, $galleryId, $file) : null;

        if ($sourcePath === null) {
            $missingFiles[] = ['photo_id' => $photoId, 'gallery_id' => $galleryId, 'file' => $file];
            $skippedPhotos++;
            continue;
        }

        $seenTargetFiles[$fileKey] = $photoId;

        if ($dryRun) {
            $copiedPhotos++;
            $insertedPhotos++;
            continue;
        }

        try {
            galerie_ensure_directories($galleryId);
            [$mime] = galerie_allowed_upload($sourcePath);
            $targetPath = galerie_gallery_dir($galleryId) . '/' . $file;
            $thumbPath = galerie_gallery_small_dir($galleryId) . '/' . $file;

            [$width, $height, $filesize] = galerie_resize_to_file(
                $sourcePath,
                $targetPath,
                $mime,
                galerie_orig_width_limit(),
                galerie_orig_height_limit()
            );
            galerie_resize_to_file(
                $targetPath,
                $thumbPath,
                $mime,
                galerie_thumb_width_limit(),
                galerie_thumb_height_limit()
            );

            $photoInsert->execute([
                ':id' => $photoId,
                ':poradi' => (int)($row['poradi'] ?? 0),
                ':galerie_id' => $galleryId,
                ':nazev_cz' => migrate_text($row['nazev_cz'] ?? ''),
                ':nazev_en' => migrate_text($row['nazev_en'] ?? ''),
                ':soubor' => $file,
                ':mime_type' => $mime,
                ':width' => $width,
                ':height' => $height,
                ':filesize' => $filesize,
                ':valid' => 1,
                ':user_i' => 'migration_old',
                ':user_u' => 'migration_old',
            ]);

            $copiedPhotos++;
            $insertedPhotos++;
            $processedByGallery[$galleryId] = ($processedByGallery[$galleryId] ?? 0) + 1;
        } catch (Throwable $e) {
            $imageErrors[] = ['photo_id' => $photoId, 'gallery_id' => $galleryId, 'file' => $file, 'error' => $e->getMessage()];
            $skippedPhotos++;
        }
    }

    if (!$dryRun) {
        $target->commit();
        $target->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
} catch (Throwable $e) {
    if (!$dryRun && $target->inTransaction()) {
        $target->rollBack();
    }
    if (!$dryRun) {
        $target->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    throw $e;
}

$targetCountsAfter = $dryRun ? $targetCountsBefore : [
    'galerie_typ' => migrate_count($target, 'galerie_typ'),
    'galerie' => migrate_count($target, 'galerie'),
    'galerie_photo' => migrate_count($target, 'galerie_photo'),
];

arsort($processedByGallery);
$missingByGallery = [];
foreach ($missingFiles as $missing) {
    $gid = (int)$missing['gallery_id'];
    $missingByGallery[$gid] = ($missingByGallery[$gid] ?? 0) + 1;
}
arsort($missingByGallery);

$report = [];
$report[] = '# 001 Fotogalerie Migrace Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- Zdroj DB: `' . $oldDbName . '`';
$report[] = '- Cil DB: `' . $targetDbName . '`';
$report[] = '- Zdroj souboru: `' . implode('`, `', $sourceMediaRoots) . '`';
$report[] = '- Cil souboru: `' . galerie_media_root() . '`';
$report[] = '- Zdroj je cilove `media/galerie`: ' . ($sourceUsesTargetMedia ? 'ano, reset maze jen `small` podslozky' : 'ne, reset maze cely cilovy media adresar');
$report[] = '- Rezim: ' . ($dryRun ? 'dry-run' : 'zapis');
$report[] = '- Reset cilovych tabulek: ' . ($reset ? 'ano' : 'ne');
$report[] = '';
$report[] = '## Pocty';
$report[] = '';
$report[] = '| Oblast | Old | Cil pred | Cil po |';
$report[] = '| --- | ---: | ---: | ---: |';
foreach (['galerie_typ', 'galerie', 'galerie_photo'] as $table) {
    $report[] = '| `' . $table . '` | ' . $oldCounts[$table] . ' | ' . $targetCountsBefore[$table] . ' | ' . $targetCountsAfter[$table] . ' |';
}
$report[] = '';
$report[] = '## Fotky';
$report[] = '';
$report[] = '- Vlozeno fotek: ' . $insertedPhotos;
$report[] = '- Zkopirovano/zpracovano souboru: ' . $copiedPhotos;
$report[] = '- Preskoceno fotek: ' . $skippedPhotos;
$report[] = '- Chybejici soubory: ' . count($missingFiles);
$report[] = '- Duplicitni fotky podle galerie/souboru: ' . count($duplicatePhotos);
$report[] = '- Chyby zpracovani obrazku: ' . count($imageErrors);
$report[] = '';
$report[] = '## Nejvice zpracovanych galerii';
$report[] = '';
$report[] = '| Galerie ID | Fotky |';
$report[] = '| ---: | ---: |';
foreach (array_slice($processedByGallery, 0, 30, true) as $gid => $count) {
    $report[] = '| ' . $gid . ' | ' . $count . ' |';
}
$report[] = '';
$report[] = '## Nejvice chybejicich souboru';
$report[] = '';
$report[] = '| Galerie ID | Chybi |';
$report[] = '| ---: | ---: |';
foreach (array_slice($missingByGallery, 0, 30, true) as $gid => $count) {
    $report[] = '| ' . $gid . ' | ' . $count . ' |';
}

if ($imageErrors !== []) {
    $report[] = '';
    $report[] = '## Chyby Obrazku';
    $report[] = '';
    $report[] = '| Photo ID | Galerie ID | Soubor | Chyba |';
    $report[] = '| ---: | ---: | --- | --- |';
    foreach (array_slice($imageErrors, 0, 200) as $error) {
        $report[] = '| ' . (int)$error['photo_id'] . ' | ' . (int)$error['gallery_id'] . ' | `' . str_replace('`', '', (string)$error['file']) . '` | ' . str_replace('|', '/', (string)$error['error']) . ' |';
    }
}

if ($duplicatePhotos !== []) {
    $report[] = '';
    $report[] = '## Duplicitni Fotky';
    $report[] = '';
    $report[] = '| Photo ID | Galerie ID | Soubor | Ponechano Photo ID |';
    $report[] = '| ---: | ---: | --- | ---: |';
    foreach ($duplicatePhotos as $duplicate) {
        $report[] = '| ' . (int)$duplicate['photo_id'] . ' | ' . (int)$duplicate['gallery_id'] . ' | `' . str_replace('`', '', (string)$duplicate['file']) . '` | ' . (int)$duplicate['kept_photo_id'] . ' |';
    }
}

if ($missingFiles !== []) {
    $report[] = '';
    $report[] = '## Chybejici Soubory - prvnich 300';
    $report[] = '';
    $report[] = '| Photo ID | Galerie ID | Soubor |';
    $report[] = '| ---: | ---: | --- |';
    foreach (array_slice($missingFiles, 0, 300) as $missing) {
        $report[] = '| ' . (int)$missing['photo_id'] . ' | ' . (int)$missing['gallery_id'] . ' | `' . str_replace('`', '', (string)$missing['file']) . '` |';
    }
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo "Migrace fotogalerie dokoncena.\n";
echo "Report: {$reportFile}\n";
echo "Typy: {$targetCountsAfter['galerie_typ']}, galerie: {$targetCountsAfter['galerie']}, fotky: {$targetCountsAfter['galerie_photo']}\n";
echo "Chybejici soubory: " . count($missingFiles) . ", chyby obrazku: " . count($imageErrors) . "\n";
