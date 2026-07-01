<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');

$oldDbName = 'xqanto_cz_old';
$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/003_news_typ_migrate_' . date('Ymd_His') . '.md';
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

function news_typ_migrate_count(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$oldRows = $old->query('SELECT * FROM news_typ ORDER BY ID ASC')->fetchAll();
$oldCount = count($oldRows);
$targetBefore = news_typ_migrate_count($target, 'news_typ');

if ($targetBefore > 0 && !$reset && !$dryRun) {
    fwrite(STDERR, "Cilova tabulka news_typ neni prazdna ({$targetBefore}). Pouzij --reset nebo nejdriv pust --dry-run.\n");
    exit(1);
}

$validCounts = [
    0 => 0,
    1 => 0,
];
$rowsToInsert = [];

foreach ($oldRows as $row) {
    $valid = (int)($row['Valid'] ?? 0) === 1 ? 1 : 0;
    $validCounts[$valid]++;

    $rowsToInsert[] = [
        'id' => (int)$row['ID'],
        'poradi' => (int)($row['Poradi'] ?? 0),
        'nazev_cz' => (string)($row['Nazev_cz'] ?? ''),
        'nazev_en' => (string)($row['Nazev_en'] ?? ''),
        'popis_cz' => (string)($row['Popis_cz'] ?? ''),
        'popis_en' => (string)($row['Popis_en'] ?? ''),
        'color' => '',
        'valid' => $valid,
        'user_i' => 'migration',
        'user_u' => 'migration',
    ];
}

if (!$dryRun) {
    $target->beginTransaction();
    try {
        if ($reset) {
            $target->exec('DELETE FROM news_typ');
        }

        $insert = $target->prepare(
            'INSERT INTO news_typ
                (id, poradi, nazev_cz, nazev_en, popis_cz, popis_en, color, valid, user_i, user_u)
             VALUES
                (:id, :poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :color, :valid, :user_i, :user_u)'
        );

        foreach ($rowsToInsert as $row) {
            $insert->execute([
                ':id' => $row['id'],
                ':poradi' => $row['poradi'],
                ':nazev_cz' => $row['nazev_cz'],
                ':nazev_en' => $row['nazev_en'],
                ':popis_cz' => $row['popis_cz'],
                ':popis_en' => $row['popis_en'],
                ':color' => $row['color'],
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

$targetAfter = $dryRun ? $targetBefore : news_typ_migrate_count($target, 'news_typ');

$report = [];
$report[] = '# 003 Typy Novinek Migrace Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- Zdroj DB: `' . $oldDbName . '`';
$report[] = '- Zdroj tabulka: `news_typ`';
$report[] = '- Cil DB: `' . $targetDbName . '`';
$report[] = '- Cil tabulka: `news_typ`';
$report[] = '- Rezim: ' . ($dryRun ? 'dry-run' : 'zapis');
$report[] = '- Reset cilove tabulky: ' . ($reset ? 'ano' : 'ne');
$report[] = '';
$report[] = '## Pocty';
$report[] = '';
$report[] = '| Oblast | Pocet |';
$report[] = '| --- | ---: |';
$report[] = '| Old `news_typ` | ' . $oldCount . ' |';
$report[] = '| Cil pred `news_typ` | ' . $targetBefore . ' |';
$report[] = '| Cil po `news_typ` | ' . $targetAfter . ' |';
$report[] = '| Vkladanych radku | ' . count($rowsToInsert) . ' |';
$report[] = '| Validni | ' . $validCounts[1] . ' |';
$report[] = '| Nevalidni | ' . $validCounts[0] . ' |';
$report[] = '';
$report[] = '## Migrovane Zaznamy';
$report[] = '';
$report[] = '| ID | Poradi | Nazev CZ | Nazev EN | Valid |';
$report[] = '| ---: | ---: | --- | --- | ---: |';
foreach ($rowsToInsert as $row) {
    $report[] = '| ' . $row['id'] . ' | ' . $row['poradi'] . ' | ' . $row['nazev_cz'] . ' | ' . $row['nazev_en'] . ' | ' . $row['valid'] . ' |';
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo ($dryRun ? "DRY RUN" : "MIGRACE HOTOVA") . "\n";
echo "Old: {$oldCount}\n";
echo "Cil pred: {$targetBefore}\n";
echo "Cil po: {$targetAfter}\n";
echo "Report: {$reportFile}\n";
