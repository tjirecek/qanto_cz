<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Tento skript lze spustit pouze z CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../../config.php';
require_once SEC_DIR . '/functions/fun_migrations.php';

/**
 * @return array{env: string, file: string, confirm_production: string, user: string, note: string}
 */
function migration_cli_parse_args(array $argv): array
{
    $options = [
        'env' => 'local',
        'file' => '',
        'confirm_production' => '',
        'user' => get_current_user() ?: 'cli',
        'note' => '',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--env=')) {
            $options['env'] = substr($arg, 6);
            continue;
        }

        if (str_starts_with($arg, '--confirm-production=')) {
            $options['confirm_production'] = substr($arg, 21);
            continue;
        }

        if (str_starts_with($arg, '--user=')) {
            $options['user'] = substr($arg, 7);
            continue;
        }

        if (str_starts_with($arg, '--note=')) {
            $options['note'] = substr($arg, 7);
            continue;
        }

        if ($arg === '--help' || $arg === '-h') {
            migration_cli_usage(0);
        }

        if ($options['file'] === '') {
            $options['file'] = $arg;
            continue;
        }

        fwrite(STDERR, "Neznamy argument: {$arg}\n");
        migration_cli_usage(1);
    }

    $options['env'] = strtolower(trim($options['env']));
    if (!in_array($options['env'], ['local', 'production'], true)) {
        fwrite(STDERR, "Neplatne prostredi. Pouzij --env=local nebo --env=production.\n");
        exit(1);
    }

    if ($options['file'] === '') {
        fwrite(STDERR, "Chybi SQL soubor.\n");
        migration_cli_usage(1);
    }

    return $options;
}

function migration_cli_usage(int $exitCode): void
{
    $script = 'php secure/scripts/apply_migration.php';
    echo <<<TXT
Pouziti:
  {$script} --env=local secure/sql/20260621_schema_migrations.sql
  {$script} --env=production --confirm-production=DBNAME secure/sql/20260621_schema_migrations.sql

Volby:
  --env=local|production          Cileve prostredi. Vychozi je local.
  --confirm-production=DBNAME     Povinne pro produkci, musi odpovidat dbname z config.ini.
  --user=JMENO                    Hodnota pro schema_migrations.applied_by.
  --note=TEXT                     Volitelna poznamka do schema_migrations.note.

Skript spousti presne jeden SQL soubor ze secure/sql a po uspesnem spusteni zapise schema_migrations.

TXT;
    exit($exitCode);
}

function migration_cli_resolve_sql_file(string $file): string
{
    $path = $file;
    if (!str_starts_with($path, '/')) {
        $path = ROOT_DIR . '/' . ltrim($path, '/');
    }

    $real = realpath($path);
    if ($real === false || !is_file($real)) {
        throw new RuntimeException('SQL soubor neexistuje: ' . $file);
    }

    $sqlDir = realpath(SEC_DIR . '/sql');
    if ($sqlDir === false || !str_starts_with($real, $sqlDir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('SQL soubor musi byt v adresari secure/sql.');
    }

    if (!str_ends_with(strtolower($real), '.sql')) {
        throw new RuntimeException('Soubor musi mit priponu .sql.');
    }

    return $real;
}

/**
 * @return array<string, mixed>
 */
function migration_cli_load_config(string $env): array
{
    $iniPath = $env === 'local'
        ? ROOT_DIR . '/ini/config_local.ini'
        : ROOT_DIR . '/ini/config.ini';

    if (!is_file($iniPath)) {
        throw new RuntimeException('INI soubor nenalezen: ' . $iniPath);
    }

    $config = parse_ini_file($iniPath, false, INI_SCANNER_TYPED);
    if (!is_array($config)) {
        throw new RuntimeException('Nelze nacist INI soubor: ' . $iniPath);
    }

    $config['_ini_path'] = $iniPath;
    return $config;
}

function migration_cli_connect(array $config): PDO
{
    $host = (string)($config['host'] ?? '127.0.0.1');
    $port = (int)($config['port'] ?? 3306);
    $db = (string)($config['dbname'] ?? '');
    $user = (string)($config['user'] ?? '');
    $pass = (string)($config['password'] ?? '');

    if ($db === '' || $user === '') {
        throw new RuntimeException('V INI chybi dbname nebo user.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
    }

    return new PDO($dsn, $user, $pass, $options);
}

function migration_cli_print_target(string $env, array $config, string $sqlFile, string $checksum): void
{
    echo 'Prostredi: ' . $env . PHP_EOL;
    echo 'INI: ' . (string)($config['_ini_path'] ?? '') . PHP_EOL;
    echo 'DB host: ' . (string)($config['host'] ?? '') . PHP_EOL;
    echo 'DB name: ' . (string)($config['dbname'] ?? '') . PHP_EOL;
    echo 'SQL soubor: ' . $sqlFile . PHP_EOL;
    echo 'Checksum: ' . $checksum . PHP_EOL;
}

function migration_cli_assert_production_confirmed(array $config, string $confirmProduction): void
{
    $db = (string)($config['dbname'] ?? '');
    if ($confirmProduction !== $db) {
        throw new RuntimeException(
            'Produkce vyzaduje potvrzeni --confirm-production=' . $db . ' podle dbname z config.ini.'
        );
    }
}

function migration_cli_is_applied(PDO $pdo, string $migration): ?array
{
    migrations_prepare_table($pdo);

    $stmt = $pdo->prepare("
        SELECT id, migration, checksum, applied_at, applied_by, environment
        FROM schema_migrations
        WHERE migration = :migration
        LIMIT 1
    ");
    $stmt->execute([':migration' => $migration]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function migration_cli_record_applied(
    PDO $pdo,
    string $migration,
    string $checksum,
    string $description,
    string $appliedBy,
    string $environment,
    string $note
): void {
    $stmt = $pdo->prepare("
        INSERT INTO schema_migrations (migration, checksum, description, applied_by, environment, note)
        VALUES (:migration, :checksum, :description, :applied_by, :environment, :note)
    ");
    $stmt->execute([
        ':migration' => $migration,
        ':checksum' => $checksum,
        ':description' => $description,
        ':applied_by' => $appliedBy,
        ':environment' => $environment,
        ':note' => $note,
    ]);
}

try {
    $args = migration_cli_parse_args($argv);
    $sqlFile = migration_cli_resolve_sql_file($args['file']);
    $migration = basename($sqlFile);
    $checksum = hash_file('sha256', $sqlFile);
    if ($checksum === false) {
        throw new RuntimeException('Nelze spocitat checksum SQL souboru.');
    }

    $config = migration_cli_load_config($args['env']);
    migration_cli_print_target($args['env'], $config, $sqlFile, $checksum);

    if ($args['env'] === 'production') {
        migration_cli_assert_production_confirmed($config, $args['confirm_production']);
    }

    $pdo = migration_cli_connect($config);
    $applied = migration_cli_is_applied($pdo, $migration);
    if ($applied !== null) {
        echo 'Migrace uz je zapsana v schema_migrations: #' . (string)$applied['id'] . PHP_EOL;
        echo 'Aplikovano: ' . (string)$applied['applied_at'] . PHP_EOL;
        echo 'Spousteni znovu bylo zastaveno.' . PHP_EOL;
        exit(0);
    }

    echo 'Spoustim SQL...' . PHP_EOL;
    $sql = file_get_contents($sqlFile);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('SQL soubor je prazdny nebo nejde nacist.');
    }

    $pdo->exec($sql);
    migration_cli_record_applied(
        $pdo,
        $migration,
        $checksum,
        migrations_file_description($sqlFile),
        trim((string)$args['user']) !== '' ? trim((string)$args['user']) : 'cli',
        $args['env'],
        (string)$args['note']
    );

    echo 'Hotovo. Migrace zapsana do schema_migrations.' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
