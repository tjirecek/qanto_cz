<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
define('ROOT_DIR', $rootDir);
define('BASE_URL', '/');

require_once $rootDir . '/secure/functions/fun_rep_akce.php';

date_default_timezone_set('Europe/Prague');

$run = in_array('--run', $argv, true);
$force = in_array('--force', $argv, true);
$limit = 0;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int)substr($arg, 8));
    }
}

$config = parse_ini_file($rootDir . '/ini/config_local.ini', false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist ini/config_local.ini\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . (string)$config['host'] . ';port=' . (int)$config['port'] . ';dbname=' . (string)$config['dbname'] . ';charset=utf8mb4',
    (string)$config['user'],
    (string)$config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);

$sql = "SELECT id, nazev_cz, pdf_file, legacy_pdf_file, legacy_pdf_path
        FROM rep_akce
        WHERE legacy_pdf_path <> ''";
if (!$force) {
    $sql .= " AND pdf_file = ''";
}
$sql .= ' ORDER BY id ASC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}
$rows = $pdo->query($sql)->fetchAll() ?: [];

if (!$run) {
    echo "DRY RUN - pro zapis pouzij --run.\n";
}

$checked = 0;
$copied = 0;
$skipped = 0;
$missing = 0;
$errors = 0;
$missingRows = [];
$update = $pdo->prepare('UPDATE rep_akce SET pdf_file = :pdf_file, pdf_original_name = :pdf_original_name, pdf_filesize = :pdf_filesize, user_u = :user_u WHERE id = :id');

foreach ($rows as $row) {
    $checked++;
    $id = (int)$row['id'];
    $title = (string)$row['nazev_cz'];
    $sourceRelative = ltrim((string)$row['legacy_pdf_path'], '/');
    $sourceAbsolute = $rootDir . '/' . $sourceRelative;
    if (!is_file($sourceAbsolute)) {
        $missing++;
        $missingRows[] = $id . ' | ' . $title . ' | ' . $sourceRelative;
        continue;
    }

    $targetDir = rep_akce_ensure_offer_dir($id, $title);
    $baseName = rep_akce_slug(pathinfo((string)($row['legacy_pdf_file'] ?: basename($sourceRelative)), PATHINFO_FILENAME), 'nabidka');
    $targetName = $baseName . '.pdf';
    $targetRelative = $targetDir . '/' . $targetName;
    $targetAbsolute = $rootDir . '/' . $targetRelative;
    $suffix = 1;
    while (is_file($targetAbsolute) && !$force) {
        if (filesize($targetAbsolute) === filesize($sourceAbsolute)) {
            $skipped++;
            if ($run && (string)$row['pdf_file'] === '') {
                $update->execute([
                    ':pdf_file' => $targetRelative,
                    ':pdf_original_name' => basename($sourceRelative),
                    ':pdf_filesize' => (int)filesize($targetAbsolute),
                    ':user_u' => 'akce-pdf-import',
                    ':id' => $id,
                ]);
            }
            continue 2;
        }
        $targetName = $baseName . '-' . $suffix . '.pdf';
        $targetRelative = $targetDir . '/' . $targetName;
        $targetAbsolute = $rootDir . '/' . $targetRelative;
        $suffix++;
    }

    if (!$run) {
        $copied++;
        continue;
    }

    try {
        if (!copy($sourceAbsolute, $targetAbsolute)) {
            throw new RuntimeException('kopirovani selhalo');
        }
        $update->execute([
            ':pdf_file' => $targetRelative,
            ':pdf_original_name' => basename($sourceRelative),
            ':pdf_filesize' => (int)filesize($targetAbsolute),
            ':user_u' => 'akce-pdf-import',
            ':id' => $id,
        ]);
        $copied++;
    } catch (Throwable $e) {
        $errors++;
        echo sprintf("ERR #%d: %s\n", $id, $e->getMessage());
    }
}

echo "Souhrn:\n";
echo "- zkontrolovano: {$checked}\n";
echo "- pripraveno/zkopirovano: {$copied}\n";
echo "- preskoceno existujici: {$skipped}\n";
echo "- chybi zdroj: {$missing}\n";
echo "- chyby: {$errors}\n";
if ($missingRows !== []) {
    echo "Chybejici PDF:\n";
    foreach ($missingRows as $line) {
        echo "- {$line}\n";
    }
}
