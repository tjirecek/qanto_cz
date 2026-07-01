<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');

$oldDbName = 'xqanto_cz_old';
$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/002_staticke_texty_migrate_' . date('Ymd_His') . '.md';
$reset = in_array('--reset', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Nelze vytvorit report adresar: {$reportDir}\n");
    exit(1);
}

$configPath = $rootDir . '/ini/config_local.ini';
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

function stattext_migrate_count(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function stattext_migrate_code(array $row, array $duplicateNumbers): string
{
    $number = preg_replace('~[^0-9]+~', '', (string)($row['cislo'] ?? ''));
    if ($number === '') {
        $number = (string)(int)($row['ID'] ?? 0);
    }

    $code = 'text_' . $number;
    if (isset($duplicateNumbers[(int)($row['cislo'] ?? 0)])) {
        $code .= '_' . (int)($row['ID'] ?? 0);
    }

    return $code;
}

function stattext_migrate_gallery_id(PDO $target, mixed $galleryId): int
{
    $id = (int)($galleryId ?? 0);
    if ($id <= 0) {
        return 0;
    }

    static $validGalleryIds = null;
    if ($validGalleryIds === null) {
        $ids = $target->query('SELECT id FROM galerie')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $validGalleryIds = array_flip(array_map('intval', $ids));
    }

    return isset($validGalleryIds[$id]) ? $id : 0;
}

$oldCount = stattext_migrate_count($old, 'texty');
$targetBefore = stattext_migrate_count($target, 'stat_texty');

if ($targetBefore > 0 && !$reset && !$dryRun) {
    fwrite(STDERR, "Cilova tabulka stat_texty neni prazdna ({$targetBefore}). Pouzij --reset nebo nejdriv pust --dry-run.\n");
    exit(1);
}

$duplicates = [];
$duplicateStmt = $old->query('SELECT cislo FROM texty GROUP BY cislo HAVING COUNT(*) > 1');
foreach ($duplicateStmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $cislo) {
    $duplicates[(int)$cislo] = true;
}

$oldRows = $old->query('SELECT * FROM texty ORDER BY ID ASC')->fetchAll();
$validCounts = [
    0 => 0,
    1 => 0,
];
$duplicateCodes = [];
$usedCodes = [];
$rowsToInsert = [];

foreach ($oldRows as $row) {
    $code = stattext_migrate_code($row, $duplicates);
    if (isset($usedCodes[$code])) {
        $duplicateCodes[] = [
            'code' => $code,
            'first_id' => $usedCodes[$code],
            'duplicate_id' => (int)$row['ID'],
        ];
        $code .= '_' . (int)$row['ID'];
    }
    $usedCodes[$code] = (int)$row['ID'];

    $valid = (int)($row['valid'] ?? 0) === 1 ? 1 : 0;
    $validCounts[$valid]++;

    $rowsToInsert[] = [
        'id' => (int)$row['ID'],
        'code' => $code,
        'nazev_cz' => (string)($row['nazev_cz'] ?? ''),
        'nazev_en' => (string)($row['nazev_en'] ?? ''),
        'text_cz' => (string)($row['text_cz'] ?? ''),
        'text_en' => (string)($row['text_en'] ?? ''),
        'galerie_id' => stattext_migrate_gallery_id($target, $row['galerie_id'] ?? 0),
        'col' => 12,
        'valid' => $valid,
        'user_i' => 'migration',
        'user_u' => 'migration',
    ];
}

if (!$dryRun) {
    $target->beginTransaction();
    try {
        if ($reset) {
            $target->exec('DELETE FROM stat_texty');
        }

        $insert = $target->prepare(
            'INSERT INTO stat_texty
                (id, code, nazev_cz, nazev_en, text_cz, text_en, galerie_id, col, valid, user_i, user_u)
             VALUES
                (:id, :code, :nazev_cz, :nazev_en, :text_cz, :text_en, :galerie_id, :col, :valid, :user_i, :user_u)'
        );

        foreach ($rowsToInsert as $row) {
            $insert->execute([
                ':id' => $row['id'],
                ':code' => $row['code'],
                ':nazev_cz' => $row['nazev_cz'],
                ':nazev_en' => $row['nazev_en'],
                ':text_cz' => $row['text_cz'],
                ':text_en' => $row['text_en'],
                ':galerie_id' => $row['galerie_id'],
                ':col' => $row['col'],
                ':valid' => $row['valid'],
                ':user_i' => $row['user_i'],
                ':user_u' => $row['user_u'],
            ]);
        }

        $target->commit();
    } catch (Throwable $e) {
        if ($target->inTransaction()) {
            $target->rollBack();
        }
        throw $e;
    }
}

$targetAfter = $dryRun ? $targetBefore : stattext_migrate_count($target, 'stat_texty');

$report = [];
$report[] = '# 002 Staticke Texty Migrace Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- Zdroj DB: `' . $oldDbName . '`';
$report[] = '- Zdroj tabulka: `texty`';
$report[] = '- Cil DB: `' . $targetDbName . '`';
$report[] = '- Cil tabulka: `stat_texty`';
$report[] = '- Rezim: ' . ($dryRun ? 'dry-run' : 'zapis');
$report[] = '- Reset cilove tabulky: ' . ($reset ? 'ano' : 'ne');
$report[] = '';
$report[] = '## Pocty';
$report[] = '';
$report[] = '| Oblast | Pocet |';
$report[] = '| --- | ---: |';
$report[] = '| Old `texty` | ' . $oldCount . ' |';
$report[] = '| Cil pred `stat_texty` | ' . $targetBefore . ' |';
$report[] = '| Cil po `stat_texty` | ' . $targetAfter . ' |';
$report[] = '| Vkladanych radku | ' . count($rowsToInsert) . ' |';
$report[] = '| Validni | ' . $validCounts[1] . ' |';
$report[] = '| Nevalidni | ' . $validCounts[0] . ' |';
$report[] = '';
$report[] = '## Duplicitni `cislo` Ve Zdroji';
$report[] = '';
if ($duplicates === []) {
    $report[] = '- Zadna duplicita.';
} else {
    $report[] = '| Cislo | Reseni |';
    $report[] = '| ---: | --- |';
    foreach (array_keys($duplicates) as $cislo) {
        $report[] = '| ' . $cislo . ' | code `text_' . $cislo . '_{ID}` |';
    }
}
$report[] = '';
$report[] = '## Duplicitni Vysledne Kody';
$report[] = '';
if ($duplicateCodes === []) {
    $report[] = '- Zadny konflikt.';
} else {
    $report[] = '| Code | Prvni ID | Duplicitni ID |';
    $report[] = '| --- | ---: | ---: |';
    foreach ($duplicateCodes as $duplicateCode) {
        $report[] = '| `' . $duplicateCode['code'] . '` | ' . $duplicateCode['first_id'] . ' | ' . $duplicateCode['duplicate_id'] . ' |';
    }
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo ($dryRun ? "DRY RUN" : "MIGRACE HOTOVA") . "\n";
echo "Old: {$oldCount}\n";
echo "Cil pred: {$targetBefore}\n";
echo "Cil po: {$targetAfter}\n";
echo "Report: {$reportFile}\n";
