<?php
declare(strict_types=1);

final class CronLogContext
{
    public PDO $pdo;
    public string $cronName;
    public ?string $scriptPath;
    public ?string $sourceName;
    public ?string $sourcePath;
    public string $startedAt;
    public float $startedTs;
    public bool $completed = false;
    public int $processedCount = 0;
    public string $result = 'ERROR';
    public string $message = '';

    public function __construct(PDO $pdo, string $cronName, array $meta = [])
    {
        $this->pdo = $pdo;
        $this->cronName = $cronName;
        $this->scriptPath = isset($meta['script_path']) ? (string)$meta['script_path'] : null;
        $this->sourceName = isset($meta['source_name']) ? (string)$meta['source_name'] : null;
        $this->sourcePath = isset($meta['source_path']) ? (string)$meta['source_path'] : null;
        $this->startedAt = date('Y-m-d H:i:s');
        $this->startedTs = microtime(true);
    }
}

function cronLogEnsureTable(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS log_cron (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cron_name VARCHAR(190) NOT NULL,
            script_path VARCHAR(255) DEFAULT NULL,
            source_name VARCHAR(255) DEFAULT NULL,
            source_path TEXT DEFAULT NULL,
            processed_count INT NOT NULL DEFAULT 0,
            result VARCHAR(20) NOT NULL,
            message TEXT DEFAULT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME NOT NULL,
            duration_ms INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_log_cron_name_started (cron_name, started_at),
            KEY idx_log_cron_result (result),
            KEY idx_log_cron_finished (finished_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $done = true;
}

function cronLogStart(PDO $pdo, string $cronName, array $meta = []): CronLogContext
{
    cronLogEnsureTable($pdo);

    if (!isset($GLOBALS['__cron_log_shutdown_registered'])) {
        register_shutdown_function('cronLogShutdownFlush');
        $GLOBALS['__cron_log_shutdown_registered'] = true;
    }

    $ctx = new CronLogContext($pdo, $cronName, $meta);
    $GLOBALS['__cron_log_active'][spl_object_id($ctx)] = $ctx;

    return $ctx;
}

function cronLogNote(?CronLogContext $ctx, string $message): void
{
    if ($ctx === null || $ctx->completed) {
        return;
    }

    $ctx->message = $message;
}

function cronLogComplete(?CronLogContext $ctx, string $result, int $processedCount = 0, string $message = ''): void
{
    if ($ctx === null || $ctx->completed) {
        return;
    }

    $ctx->result = $result;
    $ctx->processedCount = max(0, $processedCount);
    if ($message !== '') {
        $ctx->message = $message;
    }

    cronLogWrite($ctx);
}

function cronLogShutdownFlush(): void
{
    if (empty($GLOBALS['__cron_log_active']) || !is_array($GLOBALS['__cron_log_active'])) {
        return;
    }

    $fatal = error_get_last();
    $fatalMessage = '';
    if (is_array($fatal)) {
        $fatalMessage = trim(
            sprintf(
                '%s in %s:%d',
                (string)($fatal['message'] ?? 'Fatal error'),
                (string)($fatal['file'] ?? ''),
                (int)($fatal['line'] ?? 0)
            )
        );
    }

    foreach ($GLOBALS['__cron_log_active'] as $ctx) {
        if (!$ctx instanceof CronLogContext || $ctx->completed) {
            continue;
        }

        if ($fatalMessage !== '' && $ctx->message === '') {
            $ctx->message = $fatalMessage;
        }

        if ($ctx->message === '') {
            $ctx->message = 'Script ended before cronLogComplete().';
        }

        cronLogWrite($ctx);
    }
}

function cronLogWrite(CronLogContext $ctx): void
{
    if ($ctx->completed) {
        return;
    }

    $finishedAt = date('Y-m-d H:i:s');
    $durationMs = (int)max(0, round((microtime(true) - $ctx->startedTs) * 1000));

    try {
        $stmt = $ctx->pdo->prepare(
            "INSERT INTO log_cron (
                cron_name,
                script_path,
                source_name,
                source_path,
                processed_count,
                result,
                message,
                started_at,
                finished_at,
                duration_ms
            ) VALUES (
                :cron_name,
                :script_path,
                :source_name,
                :source_path,
                :processed_count,
                :result,
                :message,
                :started_at,
                :finished_at,
                :duration_ms
            )"
        );

        $stmt->execute([
            ':cron_name' => $ctx->cronName,
            ':script_path' => $ctx->scriptPath,
            ':source_name' => $ctx->sourceName,
            ':source_path' => $ctx->sourcePath,
            ':processed_count' => max(0, $ctx->processedCount),
            ':result' => $ctx->result,
            ':message' => $ctx->message !== '' ? $ctx->message : null,
            ':started_at' => $ctx->startedAt,
            ':finished_at' => $finishedAt,
            ':duration_ms' => $durationMs,
        ]);
    } catch (Throwable $e) {
        error_log('log_cron write failed: ' . $e->getMessage());
    }

    $ctx->completed = true;
    unset($GLOBALS['__cron_log_active'][spl_object_id($ctx)]);
}
