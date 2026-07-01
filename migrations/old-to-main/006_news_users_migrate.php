<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');

$oldDbName = 'xqanto_cz_old';
$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/006_news_users_migrate_' . date('Ymd_His') . '.md';
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
$target->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', ''), 'NO_ZERO_DATE', '')");

function news_users_migrate_count(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function news_users_migrate_text(mixed $value): string
{
    return trim((string)($value ?? ''));
}

function news_users_migrate_date(mixed $value): string
{
    $date = trim((string)($value ?? ''));
    return $date !== '' ? $date : '0000-00-00';
}

function news_users_migrate_email_is_valid(string $email): bool
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }

    if (!function_exists('idn_to_ascii') || !str_contains($email, '@')) {
        return false;
    }

    [$local, $domain] = explode('@', $email, 2);
    if ($local === '' || $domain === '') {
        return false;
    }

    $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    return is_string($asciiDomain) && filter_var($local . '@' . $asciiDomain, FILTER_VALIDATE_EMAIL) !== false;
}

$oldRows = $old->query('SELECT * FROM news_users ORDER BY ID ASC')->fetchAll() ?: [];
$oldCount = count($oldRows);
$targetBefore = news_users_migrate_count($target, 'news_users');

if ($targetBefore > 0 && !$reset && !$dryRun) {
    fwrite(STDERR, "Cilova tabulka news_users neni prazdna ({$targetBefore}). Pouzij --reset nebo nejdriv pust --dry-run.\n");
    exit(1);
}

$validCounts = [0 => 0, 1 => 0];
$registeredCounts = [0 => 0, 1 => 0];
$invalidEmails = [];
$emails = [];

foreach ($oldRows as $row) {
    $valid = (int)($row['valid'] ?? 0) === 1 ? 1 : 0;
    $registered = (int)($row['registered'] ?? 0) === 1 ? 1 : 0;
    $validCounts[$valid]++;
    $registeredCounts[$registered]++;

    $email = mb_strtolower(news_users_migrate_text($row['email'] ?? ''), 'UTF-8');
    if ($email === '' || !news_users_migrate_email_is_valid($email)) {
        $invalidEmails[] = ['id' => (int)$row['ID'], 'email' => $email];
    } else {
        $emails[$email] = ($emails[$email] ?? 0) + 1;
    }
}
$duplicateEmails = array_filter($emails, static fn (int $count): bool => $count > 1);
arsort($duplicateEmails);

if (!$dryRun) {
    $target->beginTransaction();
    try {
        if ($reset) {
            $target->exec('DELETE FROM news_users');
        }

        $insert = $target->prepare('INSERT INTO news_users
            (id, name, email, datum_od, datum_do, registered, valid, user_i, user_u)
            VALUES (:id, :name, :email, :datum_od, :datum_do, :registered, :valid, :user_i, :user_u)');

        foreach ($oldRows as $row) {
            $insert->execute([
                ':id' => (int)$row['ID'],
                ':name' => news_users_migrate_text($row['name'] ?? ''),
                ':email' => mb_strtolower(news_users_migrate_text($row['email'] ?? ''), 'UTF-8'),
                ':datum_od' => news_users_migrate_date($row['datum_od'] ?? null),
                ':datum_do' => news_users_migrate_date($row['datum_do'] ?? null),
                ':registered' => (int)($row['registered'] ?? 0) === 1 ? 1 : 0,
                ':valid' => (int)($row['valid'] ?? 0) === 1 ? 1 : 0,
                ':user_i' => 'migration',
                ':user_u' => 'migration',
            ]);
        }

        $target->commit();
        $target->exec('ALTER TABLE news_users AUTO_INCREMENT = 1000');
    } catch (Throwable $e) {
        if ($target->inTransaction()) {
            $target->rollBack();
        }
        throw $e;
    }
}

$targetAfter = $dryRun ? $targetBefore : news_users_migrate_count($target, 'news_users');

$report = [];
$report[] = '# 006 News Users Migrace Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- Zdroj DB: `' . $oldDbName . '`';
$report[] = '- Zdroj tabulka: `news_users`';
$report[] = '- Cil DB: `' . $targetDbName . '`';
$report[] = '- Cil tabulka: `news_users`';
$report[] = '- Rezim: ' . ($dryRun ? 'dry-run' : 'zapis');
$report[] = '- Reset cilove tabulky: ' . ($reset ? 'ano' : 'ne');
$report[] = '';
$report[] = '## Pocty';
$report[] = '';
$report[] = '| Oblast | Pocet |';
$report[] = '| --- | ---: |';
$report[] = '| Old `news_users` | ' . $oldCount . ' |';
$report[] = '| Cil pred `news_users` | ' . $targetBefore . ' |';
$report[] = '| Cil po `news_users` | ' . $targetAfter . ' |';
$report[] = '| Valid 1 | ' . $validCounts[1] . ' |';
$report[] = '| Valid 0 | ' . $validCounts[0] . ' |';
$report[] = '| Registered 1 | ' . $registeredCounts[1] . ' |';
$report[] = '| Registered 0 | ' . $registeredCounts[0] . ' |';
$report[] = '| Neplatne e-maily | ' . count($invalidEmails) . ' |';
$report[] = '| Duplicitni e-maily | ' . count($duplicateEmails) . ' |';
$report[] = '';
$report[] = '## Duplicitni E-maily';
$report[] = '';
if ($duplicateEmails === []) {
    $report[] = '- Zadny duplicitni e-mail.';
} else {
    $report[] = '| E-mail | Pocet |';
    $report[] = '| --- | ---: |';
    foreach (array_slice($duplicateEmails, 0, 100) as $email => $count) {
        $report[] = '| `' . str_replace('|', '\\|', (string)$email) . '` | ' . $count . ' |';
    }
}
$report[] = '';
$report[] = '## Neplatne E-maily';
$report[] = '';
if ($invalidEmails === []) {
    $report[] = '- Zadny neplatny e-mail.';
} else {
    $report[] = '| Old ID | E-mail |';
    $report[] = '| ---: | --- |';
    foreach ($invalidEmails as $item) {
        $report[] = '| ' . $item['id'] . ' | `' . str_replace('|', '\\|', (string)$item['email']) . '` |';
    }
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo ($dryRun ? "DRY RUN" : "MIGRACE HOTOVA") . "\n";
echo "Old: {$oldCount}\n";
echo "Cil pred: {$targetBefore}\n";
echo "Cil po: {$targetAfter}\n";
echo "Duplicitni e-maily: " . count($duplicateEmails) . "\n";
echo "Neplatne e-maily: " . count($invalidEmails) . "\n";
echo "Report: {$reportFile}\n";
