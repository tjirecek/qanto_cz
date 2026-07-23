<?php
declare(strict_types=1);

/**
 * Synchronizes legacy gallery files from downloaded production storage into /media/galerie.
 *
 * Default mode is dry-run. Use --run to write changes.
 * Source small/thumb directories are intentionally ignored; thumbnails are regenerated locally.
 */

$rootDir = dirname(__DIR__);
date_default_timezone_set('Europe/Prague');

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'qanto.local';
$_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';

define('ROOT_DIR', $rootDir);
define('SEC_DIR', ROOT_DIR . '/secure');

require_once ROOT_DIR . '/functions/bootstrap.php';
require_once ROOT_DIR . '/config.php';
require_once ROOT_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_default.php';
require_once SEC_DIR . '/functions/fun_galerie.php';

$options = [
    'run' => in_array('--run', $argv, true),
    'replace_originals' => in_array('--replace-originals', $argv, true),
    'clean_thumbs' => in_array('--clean-thumbs', $argv, true),
    'update_metadata' => in_array('--update-metadata', $argv, true),
    'gallery_id' => null,
    'limit' => 0,
    'source_roots' => [],
];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--gallery=')) {
        $options['gallery_id'] = max(0, (int)substr($arg, 10));
    } elseif (str_starts_with($arg, '--limit=')) {
        $options['limit'] = max(0, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--source=')) {
        $source = trim(substr($arg, 9));
        if ($source !== '') {
            $options['source_roots'][] = $source;
        }
    }
}

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo <<<TXT
Usage:
  SERVER_ADDR=127.0.0.1 php scripts/sync_galerie_from_files.php [options]

Options:
  --run                 Write changes. Without this option only dry-run is performed.
  --replace-originals   Rebuild/overwrite existing originals from source files.
  --clean-thumbs        Remove current /media/galerie/*/small files before regeneration.
  --update-metadata     Update metadata of existing DB photo rows.
  --gallery=ID          Process only one gallery.
  --limit=N             Stop after N old photo rows.
  --source=PATH         Add source root. Can be used multiple times.

Default sources:
  _files/_galerie
  media/galerie
  /Users/tjirecek/www_dev/old-qanto_cz/_images/_galerie if it exists

Source small/thumb directories are ignored.
TXT;
    exit(0);
}

$defaultSources = [
    ROOT_DIR . '/_files/_galerie',
    galerie_media_root(),
    '/Users/tjirecek/www_dev/old-qanto_cz/_images/_galerie',
];

$sourceRoots = array_values(array_unique(array_filter(
    array_merge($options['source_roots'], $defaultSources),
    static fn (string $dir): bool => is_dir($dir)
)));

if ($sourceRoots === []) {
    fwrite(STDERR, "No source gallery directories found.\n");
    exit(1);
}

$configPath = ROOT_DIR . '/ini/config_local.ini';
$config = parse_ini_file($configPath, false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Cannot read {$configPath}\n");
    exit(1);
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$oldDbName = 'xqanto_cz_old';

$old = new PDO(
    "mysql:host={$host};port={$port};dbname={$oldDbName};charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

function sync_galerie_same_realpath(string $a, string $b): bool
{
    $realA = realpath($a);
    $realB = realpath($b);

    return $realA !== false && $realB !== false && $realA === $realB;
}

function sync_galerie_source_path(array $sourceRoots, int $galleryId, string $file): ?string
{
    $file = basename($file);
    if ($file === '') {
        return null;
    }

    foreach ($sourceRoots as $root) {
        $candidate = rtrim($root, '/') . '/' . $galleryId . '-galerie/' . $file;
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function sync_galerie_clean_thumbs(string $mediaRoot): int
{
    $deleted = 0;
    foreach (glob(rtrim($mediaRoot, '/') . '/*-galerie/small', GLOB_ONLYDIR) ?: [] as $smallDir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($smallDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }
            if (@unlink($item->getPathname())) {
                $deleted++;
            }
        }
    }

    return $deleted;
}

function sync_galerie_photo_exists(PDO $pdo, int $photoId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie_photo WHERE id = :id');
    $stmt->execute([':id' => $photoId]);

    return (int)$stmt->fetchColumn() > 0;
}

function sync_galerie_gallery_exists(PDO $pdo, int $galleryId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie WHERE id = :id');
    $stmt->execute([':id' => $galleryId]);

    return (int)$stmt->fetchColumn() > 0;
}

function sync_galerie_type_exists(PDO $pdo, int $typeId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie_typ WHERE id = :id');
    $stmt->execute([':id' => $typeId]);

    return (int)$stmt->fetchColumn() > 0;
}

function sync_galerie_text(mixed $value): string
{
    return (string)($value ?? '');
}

function sync_galerie_insert_type(PDO $target, array $row): void
{
    $stmt = $target->prepare('INSERT INTO galerie_typ
        (id, poradi, nazev_cz, nazev_en, popis_cz, popis_en, valid, user_i, user_u)
        VALUES (:id, :poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :valid, :user_i, :user_u)');
    $stmt->execute([
        ':id' => (int)$row['ID'],
        ':poradi' => (int)($row['poradi'] ?? 0),
        ':nazev_cz' => sync_galerie_text($row['nazev_cz'] ?? ''),
        ':nazev_en' => sync_galerie_text($row['nazev_en'] ?? ''),
        ':popis_cz' => sync_galerie_text($row['popis_cz'] ?? ''),
        ':popis_en' => sync_galerie_text($row['popis_en'] ?? ''),
        ':valid' => (int)($row['valid'] ?? 1),
        ':user_i' => 'sync_galerie',
        ':user_u' => 'sync_galerie',
    ]);
}

function sync_galerie_insert_gallery(PDO $target, array $row): void
{
    $galleryId = (int)$row['ID'];
    $typeId = (int)($row['galerie_typ'] ?? 0);
    $stmt = $target->prepare('INSERT INTO galerie
        (id, nazev_cz, nazev_en, datum, galerie_typ, popis_cz, popis_en, valid, user_i, user_u)
        VALUES (:id, :nazev_cz, :nazev_en, :datum, :galerie_typ, :popis_cz, :popis_en, :valid, :user_i, :user_u)');
    $stmt->execute([
        ':id' => $galleryId,
        ':nazev_cz' => sync_galerie_text($row['nazev_cz'] ?? ''),
        ':nazev_en' => sync_galerie_text($row['nazev_en'] ?? ''),
        ':datum' => ($row['datum'] ?? null) ?: null,
        ':galerie_typ' => $typeId > 0 && sync_galerie_type_exists($target, $typeId) ? $typeId : null,
        ':popis_cz' => sync_galerie_text($row['popis_cz'] ?? ''),
        ':popis_en' => sync_galerie_text($row['popis_en'] ?? ''),
        ':valid' => (int)($row['valid'] ?? 1),
        ':user_i' => 'sync_galerie',
        ':user_u' => 'sync_galerie',
    ]);

    galerie_ensure_directories($galleryId);
}

function sync_galerie_insert_photo(PDO $target, array $row, array $meta): void
{
    $stmt = $target->prepare('INSERT INTO galerie_photo
        (id, poradi, galerie_id, nazev_cz, nazev_en, soubor, mime_type, width, height, filesize, valid, user_i, user_u)
        VALUES (:id, :poradi, :galerie_id, :nazev_cz, :nazev_en, :soubor, :mime_type, :width, :height, :filesize, 1, :user_i, :user_u)');
    $stmt->execute([
        ':id' => (int)$row['ID'],
        ':poradi' => (int)($row['poradi'] ?? 0),
        ':galerie_id' => (int)$row['galerie_id'],
        ':nazev_cz' => sync_galerie_text($row['nazev_cz'] ?? ''),
        ':nazev_en' => sync_galerie_text($row['nazev_en'] ?? ''),
        ':soubor' => basename(sync_galerie_text($row['soubor'] ?? '')),
        ':mime_type' => (string)$meta['mime_type'],
        ':width' => (int)$meta['width'],
        ':height' => (int)$meta['height'],
        ':filesize' => (int)$meta['filesize'],
        ':user_i' => 'sync_galerie',
        ':user_u' => 'sync_galerie',
    ]);
}

function sync_galerie_update_photo_metadata(PDO $target, int $photoId, array $meta): void
{
    $stmt = $target->prepare('UPDATE galerie_photo
        SET mime_type = :mime_type, width = :width, height = :height, filesize = :filesize, user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':mime_type' => (string)$meta['mime_type'],
        ':width' => (int)$meta['width'],
        ':height' => (int)$meta['height'],
        ':filesize' => (int)$meta['filesize'],
        ':user_u' => 'sync_galerie',
        ':id' => $photoId,
    ]);
}

function sync_galerie_resize_or_metadata(string $sourcePath, string $targetPath, string $thumbPath, bool $writeOriginal, bool $writeThumb): array
{
    [$mime] = galerie_allowed_upload($sourcePath);

    if ($writeOriginal) {
        [$width, $height, $filesize] = galerie_resize_to_file(
            $sourcePath,
            $targetPath,
            $mime,
            galerie_orig_width_limit(),
            galerie_orig_height_limit()
        );
    } else {
        $size = getimagesize($targetPath);
        $width = is_array($size) ? (int)$size[0] : 0;
        $height = is_array($size) ? (int)$size[1] : 0;
        $filesize = filesize($targetPath) ?: 0;
    }

    if ($writeThumb) {
        galerie_resize_to_file(
            $targetPath,
            $thumbPath,
            $mime,
            galerie_thumb_width_limit(),
            galerie_thumb_height_limit()
        );
    }

    return [
        'mime_type' => $mime,
        'width' => $width,
        'height' => $height,
        'filesize' => $filesize,
    ];
}

$reportDir = ROOT_DIR . '/migrations/old-to-main/reports';
if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Cannot create report dir {$reportDir}\n");
    exit(1);
}

$stats = [
    'old_photo_rows' => 0,
    'db_photo_existing' => 0,
    'db_photo_inserted' => 0,
    'db_photo_metadata_updated' => 0,
    'gallery_inserted' => 0,
    'type_inserted' => 0,
    'originals_written' => 0,
    'originals_reused' => 0,
    'thumbs_written' => 0,
    'thumbs_deleted_before' => 0,
    'gallery_missing' => 0,
    'source_missing' => 0,
    'errors' => 0,
];
$missing = [];
$errors = [];

if (!$options['run']) {
    echo "DRY RUN - no changes. Add --run to write.\n";
}

echo "Sources:\n";
foreach ($sourceRoots as $sourceRoot) {
    echo "- {$sourceRoot}\n";
}

echo "Target: " . galerie_media_root() . "\n";
echo "Original max: " . galerie_orig_width_limit() . 'x' . galerie_orig_height_limit() . "\n";
echo "Thumb max: " . galerie_thumb_width_limit() . 'x' . galerie_thumb_height_limit() . " (small/)\n";
echo "Quality: " . galerie_image_quality() . "\n";

if ($options['clean_thumbs']) {
    if ($options['run']) {
        $stats['thumbs_deleted_before'] = sync_galerie_clean_thumbs(galerie_media_root());
    } else {
        foreach (glob(galerie_media_root() . '/*-galerie/small/*') ?: [] as $thumbFile) {
            if (is_file($thumbFile)) {
                $stats['thumbs_deleted_before']++;
            }
        }
    }
}

if ($options['run']) {
    $pdo->beginTransaction();
}

try {
    if ($options['gallery_id'] !== null && $options['gallery_id'] > 0) {
        $typeSql = 'SELECT DISTINCT gt.* FROM galerie_typ gt INNER JOIN galerie g ON g.galerie_typ = gt.ID WHERE g.ID = :gallery_id';
        $typeStmt = $old->prepare($typeSql);
        $typeStmt->execute([':gallery_id' => $options['gallery_id']]);
        $types = $typeStmt->fetchAll() ?: [];
    } else {
        $types = $old->query('SELECT * FROM galerie_typ ORDER BY ID')->fetchAll() ?: [];
    }

    foreach ($types as $type) {
        $typeId = (int)$type['ID'];
        if (sync_galerie_type_exists($pdo, $typeId)) {
            continue;
        }
        $stats['type_inserted']++;
        if ($options['run']) {
            sync_galerie_insert_type($pdo, $type);
        }
    }

    $gallerySql = 'SELECT * FROM galerie';
    $galleryParams = [];
    if ($options['gallery_id'] !== null && $options['gallery_id'] > 0) {
        $gallerySql .= ' WHERE ID = :gallery_id';
        $galleryParams[':gallery_id'] = $options['gallery_id'];
    }
    $gallerySql .= ' ORDER BY ID';
    $galleryStmt = $old->prepare($gallerySql);
    $galleryStmt->execute($galleryParams);
    foreach ($galleryStmt->fetchAll() ?: [] as $gallery) {
        $galleryId = (int)$gallery['ID'];
        if (sync_galerie_gallery_exists($pdo, $galleryId)) {
            if ($options['run']) {
                galerie_ensure_directories($galleryId);
            }
            continue;
        }
        $stats['gallery_inserted']++;
        if ($options['run']) {
            sync_galerie_insert_gallery($pdo, $gallery);
        }
    }

    $photoSql = 'SELECT * FROM galerie_photo';
    $photoParams = [];
    if ($options['gallery_id'] !== null && $options['gallery_id'] > 0) {
        $photoSql .= ' WHERE galerie_id = :gallery_id';
        $photoParams[':gallery_id'] = $options['gallery_id'];
    }
    $photoSql .= ' ORDER BY galerie_id, poradi, ID';
    if ((int)$options['limit'] > 0) {
        $photoSql .= ' LIMIT ' . (int)$options['limit'];
    }

    $photoStmt = $old->prepare($photoSql);
    $photoStmt->execute($photoParams);

    foreach ($photoStmt->fetchAll() ?: [] as $row) {
        $stats['old_photo_rows']++;
        $photoId = (int)$row['ID'];
        $galleryId = (int)$row['galerie_id'];
        $file = basename(sync_galerie_text($row['soubor'] ?? ''));

        if ($galleryId <= 0 || $file === '') {
            $stats['source_missing']++;
            $missing[] = "photo {$photoId}: invalid gallery/file";
            continue;
        }

        if (!sync_galerie_gallery_exists($pdo, $galleryId)) {
            $stats['gallery_missing']++;
            if (count($missing) < 100) {
                $missing[] = "{$galleryId}-galerie/{$file} (missing gallery)";
            }
            continue;
        }

        $photoExists = sync_galerie_photo_exists($pdo, $photoId);
        if ($photoExists) {
            $stats['db_photo_existing']++;
        }

        $sourcePath = sync_galerie_source_path($sourceRoots, $galleryId, $file);
        if ($sourcePath === null) {
            $stats['source_missing']++;
            if (count($missing) < 100) {
                $missing[] = "{$galleryId}-galerie/{$file}";
            }
            continue;
        }

        try {
            if ($options['run']) {
                galerie_ensure_directories($galleryId);
            }

            $targetPath = galerie_gallery_dir($galleryId) . '/' . $file;
            $thumbPath = galerie_gallery_small_dir($galleryId) . '/' . $file;
            $targetExists = is_file($targetPath);
            $sourceIsTarget = $targetExists && sync_galerie_same_realpath($sourcePath, $targetPath);
            $writeOriginal = !$targetExists || ($options['replace_originals'] && !$sourceIsTarget);
            $writeThumb = !is_file($thumbPath) || $writeOriginal || $options['clean_thumbs'];

            if ($options['run']) {
                $meta = sync_galerie_resize_or_metadata($sourcePath, $targetPath, $thumbPath, $writeOriginal, $writeThumb);
            } else {
                [$mime] = galerie_allowed_upload($sourcePath);
                $metaPath = $targetExists ? $targetPath : $sourcePath;
                $size = getimagesize($metaPath);
                $meta = [
                    'mime_type' => $mime,
                    'width' => is_array($size) ? (int)$size[0] : 0,
                    'height' => is_array($size) ? (int)$size[1] : 0,
                    'filesize' => filesize($metaPath) ?: 0,
                ];
            }

            if ($writeOriginal) {
                $stats['originals_written']++;
            } else {
                $stats['originals_reused']++;
            }
            if ($writeThumb) {
                $stats['thumbs_written']++;
            }

            if (!$photoExists) {
                $stats['db_photo_inserted']++;
                if ($options['run']) {
                    sync_galerie_insert_photo($pdo, $row, $meta);
                }
            } elseif ($options['update_metadata']) {
                $stats['db_photo_metadata_updated']++;
                if ($options['run']) {
                    sync_galerie_update_photo_metadata($pdo, $photoId, $meta);
                }
            }
        } catch (Throwable $e) {
            $stats['errors']++;
            if (count($errors) < 100) {
                $errors[] = "{$galleryId}-galerie/{$file}: " . $e->getMessage();
            }
        }
    }

    if ($options['run']) {
        $pdo->commit();
    }
} catch (Throwable $e) {
    if ($options['run'] && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

$report = [];
$report[] = '# Galerie Sync Report';
$report[] = '';
$report[] = '- Time: ' . date('Y-m-d H:i:s');
$report[] = '- Mode: ' . ($options['run'] ? 'run' : 'dry-run');
$report[] = '- Replace originals: ' . ($options['replace_originals'] ? 'yes' : 'no');
$report[] = '- Clean thumbs: ' . ($options['clean_thumbs'] ? 'yes' : 'no');
$report[] = '- Update metadata: ' . ($options['update_metadata'] ? 'yes' : 'no');
$report[] = '- Gallery filter: ' . (($options['gallery_id'] ?? 0) > 0 ? (string)$options['gallery_id'] : 'all');
$report[] = '- Limit: ' . ((int)$options['limit'] > 0 ? (string)$options['limit'] : 'none');
$report[] = '';
$report[] = '## Sources';
foreach ($sourceRoots as $sourceRoot) {
    $report[] = '- `' . $sourceRoot . '`';
}
$report[] = '';
$report[] = '## Stats';
foreach ($stats as $key => $value) {
    $report[] = '- `' . $key . '`: ' . $value;
}
if ($missing !== []) {
    $report[] = '';
    $report[] = '## First Missing Sources';
    foreach ($missing as $item) {
        $report[] = '- `' . $item . '`';
    }
}
if ($errors !== []) {
    $report[] = '';
    $report[] = '## First Errors';
    foreach ($errors as $item) {
        $report[] = '- `' . $item . '`';
    }
}

$reportFile = $reportDir . '/sync_galerie_from_files_' . date('Ymd_His') . '.md';
file_put_contents($reportFile, implode("\n", $report) . "\n");

echo "\nStats:\n";
foreach ($stats as $key => $value) {
    echo str_pad($key, 28) . $value . "\n";
}
echo "Report: {$reportFile}\n";

if (!$options['run']) {
    echo "\nDry-run only. Re-run with --run when ready.\n";
}
