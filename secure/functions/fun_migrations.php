<?php
declare(strict_types=1);

function migrations_prepare_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(190) NOT NULL,
            checksum CHAR(64) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_by VARCHAR(160) DEFAULT NULL,
            environment VARCHAR(40) DEFAULT NULL,
            note TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_schema_migrations_migration (migration),
            KEY idx_schema_migrations_applied_at (applied_at),
            KEY idx_schema_migrations_environment (environment)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function migrations_sql_dir(): string
{
    return defined('SEC_DIR') ? SEC_DIR . '/sql' : dirname(__DIR__) . '/sql';
}

function migrations_detect_local_environment(): bool
{
    $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

    if (
        str_contains($hostHeader, '.local')
        || in_array($remoteIp, ['127.0.0.1', '::1'], true)
        || in_array($serverAddr, ['127.0.0.1', '::1'], true)
    ) {
        return true;
    }

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        $hostName = (string)(gethostname() ?: php_uname('n'));
        return str_contains($hostName, '.local') || $hostName === 'localhost';
    }

    return false;
}

function migrations_environment_label(): string
{
    return migrations_detect_local_environment() ? 'local' : 'production';
}

/**
 * @return array<string, mixed>
 */
function migrations_current_config(): array
{
    $iniPath = migrations_environment_label() === 'local'
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

function migrations_current_db_name(PDO $pdo, array $config): string
{
    $db = trim((string)($pdo->query('SELECT DATABASE()')->fetchColumn() ?: ''));
    return $db !== '' ? $db : trim((string)($config['dbname'] ?? ''));
}

function migrations_file_description(string $path): string
{
    $handle = @fopen($path, 'rb');
    if ($handle !== false) {
        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '--')) {
                    return trim(substr($line, 2));
                }

                if (str_starts_with($line, '#')) {
                    return trim(substr($line, 1));
                }

                break;
            }
        } finally {
            fclose($handle);
        }
    }

    $name = preg_replace('~\.sql$~i', '', basename($path));
    $name = preg_replace('~^[0-9]{8}_~', '', (string)$name);
    return trim(str_replace('_', ' ', (string)$name));
}

/**
 * @return array<int, array<string, mixed>>
 */
function migrations_list_sql_files(): array
{
    $files = glob(migrations_sql_dir() . '/*.sql') ?: [];
    natsort($files);

    $rows = [];
    foreach ($files as $path) {
        if (!is_file($path)) {
            continue;
        }

        $rows[] = [
            'migration' => basename($path),
            'path' => $path,
            'checksum' => hash_file('sha256', $path) ?: '',
            'description' => migrations_file_description($path),
            'modified_at' => date('Y-m-d H:i:s', (int)filemtime($path)),
            'size' => (int)filesize($path),
        ];
    }

    return $rows;
}

/**
 * @return array<string, array<string, mixed>>
 */
function migrations_fetch_applied(PDO $pdo): array
{
    migrations_prepare_table($pdo);

    $stmt = $pdo->query("
        SELECT id, migration, checksum, description, applied_at, applied_by, environment, note
        FROM schema_migrations
        ORDER BY applied_at DESC, id DESC
    ");

    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[(string)$row['migration']] = $row;
    }

    return $rows;
}

/**
 * @return array<int, array<string, mixed>>
 */
function migrations_overview(PDO $pdo): array
{
    $files = migrations_list_sql_files();
    $applied = migrations_fetch_applied($pdo);
    $rows = [];

    foreach ($files as $file) {
        $migration = (string)$file['migration'];
        $dbRow = $applied[$migration] ?? null;

        $status = 'pending';
        if (is_array($dbRow)) {
            $dbChecksum = trim((string)($dbRow['checksum'] ?? ''));
            $status = ($dbChecksum !== '' && !hash_equals($dbChecksum, (string)$file['checksum']))
                ? 'changed'
                : 'applied';
            unset($applied[$migration]);
        }

        $rows[] = [
            'status' => $status,
            'file' => $file,
            'applied' => $dbRow,
        ];
    }

    foreach ($applied as $dbRow) {
        $rows[] = [
            'status' => 'missing_file',
            'file' => null,
            'applied' => $dbRow,
        ];
    }

    $statusOrder = [
        'pending' => 0,
        'changed' => 1,
        'missing_file' => 2,
        'applied' => 3,
    ];

    usort($rows, static function (array $a, array $b) use ($statusOrder): int {
        $statusCmp = ($statusOrder[(string)($a['status'] ?? '')] ?? 99)
            <=> ($statusOrder[(string)($b['status'] ?? '')] ?? 99);
        if ($statusCmp !== 0) {
            return $statusCmp;
        }

        $status = (string)($a['status'] ?? '');
        if ($status === 'applied') {
            $appliedAtA = (string)($a['applied']['applied_at'] ?? '');
            $appliedAtB = (string)($b['applied']['applied_at'] ?? '');
            $timeCmp = strcmp($appliedAtB, $appliedAtA);
            if ($timeCmp !== 0) {
                return $timeCmp;
            }
        }

        $migrationA = (string)($a['file']['migration'] ?? $a['applied']['migration'] ?? '');
        $migrationB = (string)($b['file']['migration'] ?? $b['applied']['migration'] ?? '');
        return strnatcasecmp($migrationB, $migrationA);
    });

    return $rows;
}

function migrations_short_checksum(?string $checksum): string
{
    $checksum = trim((string)$checksum);
    return $checksum === '' ? '' : substr($checksum, 0, 12);
}

function migrations_normalize_sql_migration(string $migration): string
{
    $migration = basename(trim($migration));
    if ($migration === '' || !preg_match('~^[0-9]{8}_[A-Za-z0-9_.-]+\.sql$~', $migration)) {
        throw new InvalidArgumentException('Neplatny nazev migrace.');
    }

    return $migration;
}

function migrations_resolve_sql_file(string $migration): string
{
    $migration = migrations_normalize_sql_migration($migration);

    $sqlDir = realpath(migrations_sql_dir());
    if ($sqlDir === false) {
        throw new RuntimeException('Adresar SQL migraci neexistuje.');
    }

    $path = realpath($sqlDir . DIRECTORY_SEPARATOR . $migration);
    if ($path === false || !is_file($path) || !str_starts_with($path, $sqlDir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('SQL soubor migrace neexistuje.');
    }

    return $path;
}

function migrations_resolve_optional_sql_file(string $migration): ?string
{
    $migration = migrations_normalize_sql_migration($migration);

    $sqlDir = realpath(migrations_sql_dir());
    if ($sqlDir === false) {
        throw new RuntimeException('Adresar SQL migraci neexistuje.');
    }

    $path = realpath($sqlDir . DIRECTORY_SEPARATOR . $migration);
    if ($path === false || !is_file($path)) {
        return null;
    }

    if (!str_starts_with($path, $sqlDir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('SQL soubor migrace je mimo povoleny adresar.');
    }

    return $path;
}

function migrations_fetch_one(PDO $pdo, string $migration): ?array
{
    migrations_prepare_table($pdo);

    $stmt = $pdo->prepare("
        SELECT id, migration, checksum, description, applied_at, applied_by, environment, note
        FROM schema_migrations
        WHERE migration = :migration
        LIMIT 1
    ");
    $stmt->execute([':migration' => $migration]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function migrations_record_applied(
    PDO $pdo,
    string $migration,
    string $checksum,
    string $description,
    string $appliedBy,
    string $environment,
    string $note = ''
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

/**
 * @return array{migration: string, deleted_record: bool, deleted_file: bool}
 */
function migrations_delete_migration(PDO $pdo, string $migration): array
{
    migrations_prepare_table($pdo);

    $migration = migrations_normalize_sql_migration($migration);
    $path = migrations_resolve_optional_sql_file($migration);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM schema_migrations WHERE migration = :migration');
        $stmt->execute([':migration' => $migration]);
        $deletedRecord = $stmt->rowCount() > 0;
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $deletedFile = false;
    if ($path !== null) {
        if (!unlink($path)) {
            throw new RuntimeException('SQL soubor migrace se nepodarilo smazat. Zaznam v DB uz byl smazan.');
        }
        $deletedFile = true;
    }

    if (!$deletedRecord && !$deletedFile) {
        throw new RuntimeException('Migrace nebyla nalezena v DB ani v secure/sql.');
    }

    return [
        'migration' => $migration,
        'deleted_record' => $deletedRecord,
        'deleted_file' => $deletedFile,
    ];
}

/**
 * @return array{migration: string, checksum: string, description: string}
 */
function migrations_apply_sql_file(
    PDO $pdo,
    string $migration,
    string $appliedBy,
    string $environment,
    string $note = ''
): array {
    $path = migrations_resolve_sql_file($migration);
    $migration = basename($path);

    if (migrations_fetch_one($pdo, $migration) !== null) {
        throw new RuntimeException('Migrace uz je zapsana v schema_migrations.');
    }

    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('SQL soubor je prazdny nebo nejde nacist.');
    }

    $checksum = hash_file('sha256', $path);
    if ($checksum === false) {
        throw new RuntimeException('Nelze spocitat checksum migrace.');
    }

    $pdo->exec($sql);

    $description = migrations_file_description($path);
    migrations_record_applied($pdo, $migration, $checksum, $description, $appliedBy, $environment, $note);

    return [
        'migration' => $migration,
        'checksum' => $checksum,
        'description' => $description,
    ];
}

function migrations_backup_dir(): string
{
    return defined('SEC_DIR') ? SEC_DIR . '/_backup/db' : dirname(__DIR__) . '/_backup/db';
}

function migrations_ensure_backup_dir(): string
{
    $dir = migrations_backup_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Nelze vytvorit adresar pro DB zalohy: ' . $dir);
    }

    $gitignore = $dir . '/.gitignore';
    if (!is_file($gitignore)) {
        file_put_contents($gitignore, "*\n!.gitignore\n!.htaccess\n");
    }

    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }

    return $dir;
}

function migrations_find_mysqldump(array $config = []): string
{
    $configured = trim((string)($config['mysqldump_path'] ?? ''));
    $candidates = [];
    if ($configured !== '') {
        $candidates[] = $configured;
    }

    $candidates[] = '/usr/bin/mysqldump';
    $candidates[] = '/usr/local/bin/mysqldump';
    $candidates[] = '/opt/homebrew/bin/mysqldump';
    $candidates[] = '/Applications/MAMP/Library/bin/mysqldump';

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    $found = trim((string)shell_exec('command -v mysqldump 2>/dev/null'));
    if ($found !== '' && is_executable($found)) {
        return $found;
    }

    throw new RuntimeException('mysqldump nebyl nalezen. Pridej ho do PATH nebo nastav INI klic mysqldump_path.');
}

function migrations_safe_backup_name(string $value): string
{
    $safe = preg_replace('~[^A-Za-z0-9_.-]+~', '_', $value);
    $safe = trim((string)$safe, '._-');
    return $safe !== '' ? $safe : 'database';
}

/**
 * @return array<int, array<string, mixed>>
 */
function migrations_list_db_backups(int $limit = 10): array
{
    $dir = migrations_backup_dir();
    $files = is_dir($dir) ? (glob($dir . '/*.sql') ?: []) : [];
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));

    $rows = [];
    foreach (array_slice($files, 0, $limit) as $path) {
        $rows[] = [
            'name' => basename($path),
            'path' => $path,
            'size' => (int)filesize($path),
            'created_at' => date('Y-m-d H:i:s', (int)filemtime($path)),
        ];
    }

    return $rows;
}

function migrations_resolve_backup_file(string $backup): string
{
    $backup = basename(trim($backup));
    if ($backup === '' || !preg_match('~^[A-Za-z0-9_.-]+\.sql$~', $backup)) {
        throw new InvalidArgumentException('Neplatny nazev backupu.');
    }

    $dir = realpath(migrations_backup_dir());
    if ($dir === false) {
        throw new RuntimeException('Adresar DB backupu neexistuje.');
    }

    $path = realpath($dir . DIRECTORY_SEPARATOR . $backup);
    if ($path === false || !is_file($path) || !str_starts_with($path, $dir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Backup soubor neexistuje.');
    }

    return $path;
}

function migrations_delete_db_backup(string $backup): string
{
    $path = migrations_resolve_backup_file($backup);
    $name = basename($path);

    if (!unlink($path)) {
        throw new RuntimeException('Backup se nepodarilo smazat.');
    }

    return $name;
}

function migrations_format_bytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * @return array{name: string, path: string, size: int, created_at: string}
 */
function migrations_create_db_backup(array $config, string $environment, string $createdBy): array
{
    $db = trim((string)($config['dbname'] ?? ''));
    $user = (string)($config['user'] ?? '');
    if ($db === '' || $user === '') {
        throw new RuntimeException('V konfiguraci chybi dbname nebo user.');
    }

    $dir = migrations_ensure_backup_dir();
    $mysqldump = migrations_find_mysqldump($config);
    $safeDb = migrations_safe_backup_name($db);
    $safeEnv = migrations_safe_backup_name($environment);
    $safeUser = migrations_safe_backup_name($createdBy);
    $target = $dir . '/' . $safeDb . '_' . date('Ymd_His') . '_' . $safeEnv . '_' . $safeUser . '.sql';

    $defaultsFile = tempnam(sys_get_temp_dir(), 'admin_mysqldump_');
    if ($defaultsFile === false) {
        throw new RuntimeException('Nelze vytvorit docasny konfiguracni soubor pro mysqldump.');
    }

    $host = (string)($config['host'] ?? '127.0.0.1');
    $port = (int)($config['port'] ?? 3306);
    $password = (string)($config['password'] ?? '');

    $defaults = "[client]\n"
        . "host={$host}\n"
        . "port={$port}\n"
        . "user={$user}\n"
        . "password={$password}\n"
        . "default-character-set=utf8mb4\n";

    file_put_contents($defaultsFile, $defaults);
    chmod($defaultsFile, 0600);

    $cmd = escapeshellarg($mysqldump)
        . ' --defaults-extra-file=' . escapeshellarg($defaultsFile)
        . ' --single-transaction --quick --routines --triggers --default-character-set=utf8mb4 '
        . escapeshellarg($db);

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['file', $target, 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorSpec, $pipes, defined('ROOT_DIR') ? ROOT_DIR : null);
    if (!is_resource($process)) {
        @unlink($defaultsFile);
        throw new RuntimeException('Nelze spustit mysqldump.');
    }

    fclose($pipes[0]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    @unlink($defaultsFile);

    if ($exitCode !== 0) {
        @unlink($target);
        $message = trim((string)$stderr);
        throw new RuntimeException('mysqldump skoncil chybou' . ($message !== '' ? ': ' . $message : '.'));
    }

    if (!is_file($target) || filesize($target) === 0) {
        @unlink($target);
        throw new RuntimeException('Backup nebyl vytvoren nebo je prazdny.');
    }

    chmod($target, 0600);

    return [
        'name' => basename($target),
        'path' => $target,
        'size' => (int)filesize($target),
        'created_at' => date('Y-m-d H:i:s', (int)filemtime($target)),
    ];
}
