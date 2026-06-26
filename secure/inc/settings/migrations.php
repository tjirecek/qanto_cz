<?php
declare(strict_types=1);

global $pdo;

require_once SEC_DIR . '/functions/fun_migrations.php';

function migrations_admin_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function migrations_admin_badge(string $status): string
{
    return match ($status) {
        'applied' => '<span class="badge bg-success">zapsáno</span>',
        'changed' => '<span class="badge bg-warning text-dark">změněno</span>',
        'missing_file' => '<span class="badge bg-danger">chybí soubor</span>',
        default => '<span class="badge bg-secondary">nezapsáno</span>',
    };
}

$rows = [];
$stats = [
    'applied' => 0,
    'pending' => 0,
    'changed' => 0,
    'missing_file' => 0,
];
$error = '';
$notice = '';

$config = [];
$currentDbName = '';
$environment = migrations_environment_label();
$backupDir = migrations_backup_dir();
$backups = migrations_list_db_backups(8);

$csrfToken = (string)admin_session_get('migration_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('migration_csrf_token', $csrfToken);
}

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    $config = migrations_current_config();
    $currentDbName = migrations_current_db_name($pdo, $config);
    $adminUser = trim((string)(admin_session_user_name() ?: admin_session_user()));
    if ($adminUser === '') {
        $adminUser = 'admin';
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array((string)($_POST['migration_action'] ?? ''), ['apply', 'backup', 'delete_backup', 'delete_migration'], true)) {
        if ((int)admin_session_prava() !== 1) {
            throw new RuntimeException('Nemáš oprávnění spravovat DB migrace.');
        }

        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }

        if ($currentDbName === '') {
            throw new RuntimeException('Nelze ověřit název cílové databáze.');
        }

        $confirmedDb = trim((string)($_POST['confirm_db'] ?? ''));
        if (!hash_equals($currentDbName, $confirmedDb)) {
            throw new RuntimeException('Potvrzení databáze nesouhlasí. Opiš přesně aktuální DB name.');
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['migration_action'] ?? '') === 'backup') {
        $backup = migrations_create_db_backup($config, $environment, $adminUser);

        $notice = 'Backup databáze byl vytvořen: ' . (string)$backup['name']
            . ' (' . migrations_format_bytes((int)$backup['size']) . ')';
        $backups = migrations_list_db_backups(8);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['migration_action'] ?? '') === 'delete_backup') {
        $deletedName = migrations_delete_db_backup((string)($_POST['backup'] ?? ''));
        $notice = 'Backup byl smazán: ' . $deletedName;
        $backups = migrations_list_db_backups(8);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['migration_action'] ?? '') === 'apply') {
        $migration = trim((string)($_POST['migration'] ?? ''));
        $result = migrations_apply_sql_file(
            $pdo,
            $migration,
            $adminUser,
            $environment,
            'Spuštěno z administrace'
        );

        $notice = 'Migrace byla spuštěna a zapsána: ' . (string)$result['migration'];
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['migration_action'] ?? '') === 'delete_migration') {
        $migration = trim((string)($_POST['migration'] ?? ''));
        $result = migrations_delete_migration($pdo, $migration);
        $deletedParts = [];
        if ((bool)$result['deleted_record']) {
            $deletedParts[] = 'záznam v DB';
        }
        if ((bool)$result['deleted_file']) {
            $deletedParts[] = 'SQL soubor';
        }

        $notice = 'Migrace byla smazána: ' . (string)$result['migration']
            . ' (' . implode(' + ', $deletedParts) . ')';
    }

    $rows = migrations_overview($pdo);
    foreach ($rows as $row) {
        $status = (string)($row['status'] ?? 'pending');
        if (array_key_exists($status, $stats)) {
            $stats[$status]++;
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<style>
    .migrations-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .migrations-table {
        min-width: 1280px;
    }

    .migrations-table .migration-file {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: .82rem;
    }

    .migrations-table .migration-note {
        max-width: 260px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .migrations-run-form {
        min-width: 280px;
    }

    .migrations-backup-path {
        word-break: break-all;
    }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">DB migrace</h1>
        <div class="small text-muted">Přehled SQL souborů v <code>secure/sql</code> a jejich evidence v tabulce <code>schema_migrations</code>.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <span class="btn btn-sm btn-light shadow-sm">prostředí: <?= migrations_admin_e($environment) ?></span>
        <span class="btn btn-sm btn-light shadow-sm">DB: <?= migrations_admin_e($currentDbName) ?></span>
        <span class="btn btn-sm btn-success shadow-sm">zapsáno: <?= (int)$stats['applied'] ?></span>
        <span class="btn btn-sm btn-secondary shadow-sm">nezapsáno: <?= (int)$stats['pending'] ?></span>
        <span class="btn btn-sm btn-warning shadow-sm">změněno: <?= (int)$stats['changed'] ?></span>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <?= migrations_admin_e($error) ?>
    </div>
<?php else: ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success">
            <?= migrations_admin_e($notice) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info small">
        SQL se spouští nad aktuálně připojenou databází. U čekající migrace opiš název databáze <code><?= migrations_admin_e($currentDbName) ?></code>; bez toho se migrace nespustí.
        Smazání migrace maže pouze evidenci v <code>schema_migrations</code> a SQL soubor v <code>secure/sql</code>, nevrací provedené změny schématu.
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary">Backup databáze</h6>
                <div class="small text-muted migrations-backup-path">Ukládá se do <code><?= migrations_admin_e($backupDir) ?></code></div>
            </div>

            <form method="post" class="migrations-run-form">
                <input type="hidden" name="migration_action" value="backup">
                <input type="hidden" name="csrf_token" value="<?= migrations_admin_e($csrfToken) ?>">
                <div class="input-group input-group-sm">
                    <input
                        type="text"
                        name="confirm_db"
                        class="form-control"
                        placeholder="opiš DB: <?= migrations_admin_e($currentDbName) ?>"
                        autocomplete="off"
                        required
                    >
                    <button type="submit" class="btn btn-outline-primary">
                        Vytvořit backup
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <?php if ($backups === []): ?>
                <div class="small text-muted">Zatím zde není žádný DB backup.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Soubor</th>
                            <th>Vytvořeno</th>
                            <th>Velikost</th>
                            <th>Akce</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td class="migration-file"><?= migrations_admin_e($backup['name'] ?? '') ?></td>
                                <td><?= migrations_admin_e($backup['created_at'] ?? '') ?></td>
                                <td><?= migrations_admin_e(migrations_format_bytes((int)($backup['size'] ?? 0))) ?></td>
                                <td>
                                    <form method="post" class="migrations-run-form">
                                        <input type="hidden" name="migration_action" value="delete_backup">
                                        <input type="hidden" name="csrf_token" value="<?= migrations_admin_e($csrfToken) ?>">
                                        <input type="hidden" name="backup" value="<?= migrations_admin_e($backup['name'] ?? '') ?>">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                name="confirm_db"
                                                class="form-control"
                                                placeholder="opiš DB: <?= migrations_admin_e($currentDbName) ?>"
                                                autocomplete="off"
                                                required
                                            >
                                            <button type="submit" class="btn btn-outline-danger">
                                                Smazat
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="m-0 fw-bold text-primary">Evidence migrací</h6>
            <span class="small text-muted">souborů: <?= count($rows) ?></span>
        </div>

        <div class="card-body">
            <div class="migrations-table-wrap">
                <table class="table table-striped table-hover table-bordered table-sm align-middle migrations-table">
                    <thead class="table-dark align-middle">
                    <tr>
                        <th>Stav</th>
                        <th>SQL soubor</th>
                        <th>Popis</th>
                        <th>Aplikováno</th>
                        <th>Prostředí</th>
                        <th>Uživatel</th>
                        <th>Checksum</th>
                        <th>Poznámka</th>
                        <th>Akce</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $file = is_array($row['file'] ?? null) ? $row['file'] : null;
                        $applied = is_array($row['applied'] ?? null) ? $row['applied'] : null;
                        $migration = (string)($file['migration'] ?? $applied['migration'] ?? '');
                        $description = (string)($applied['description'] ?? $file['description'] ?? '');
                        $checksum = (string)($applied['checksum'] ?? $file['checksum'] ?? '');
                        ?>
                        <tr>
                            <td><?= migrations_admin_badge((string)$row['status']) ?></td>
                            <td class="migration-file"><?= migrations_admin_e($migration) ?></td>
                            <td><?= migrations_admin_e($description) ?></td>
                            <td><?= migrations_admin_e($applied['applied_at'] ?? '') ?></td>
                            <td><?= migrations_admin_e($applied['environment'] ?? '') ?></td>
                            <td><?= migrations_admin_e($applied['applied_by'] ?? '') ?></td>
                            <td class="migration-file" title="<?= migrations_admin_e($checksum) ?>">
                                <?= migrations_admin_e(migrations_short_checksum($checksum)) ?>
                            </td>
                            <td class="migration-note" title="<?= migrations_admin_e($applied['note'] ?? '') ?>">
                                <?= migrations_admin_e($applied['note'] ?? '') ?>
                            </td>
                            <td>
                                <?php if ((string)$row['status'] === 'pending' && $file !== null): ?>
                                    <form method="post" class="migrations-run-form">
                                        <input type="hidden" name="migration_action" value="apply">
                                        <input type="hidden" name="csrf_token" value="<?= migrations_admin_e($csrfToken) ?>">
                                        <input type="hidden" name="migration" value="<?= migrations_admin_e($migration) ?>">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                name="confirm_db"
                                                class="form-control"
                                                placeholder="opiš DB: <?= migrations_admin_e($currentDbName) ?>"
                                                autocomplete="off"
                                                required
                                            >
                                            <button type="submit" class="btn btn-outline-danger">
                                                Spustit
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <?php if ((string)$row['status'] === 'changed'): ?>
                                    <div class="small text-warning mb-2">Soubor se liší od zapsaného checksumu.</div>
                                <?php endif; ?>

                                <?php if ($migration !== ''): ?>
                                    <form method="post" class="migrations-run-form mt-2" onsubmit="return confirm('Opravdu smazat migraci <?= migrations_admin_e($migration) ?>? Tato akce nevrací provedené změny databáze.');">
                                        <input type="hidden" name="migration_action" value="delete_migration">
                                        <input type="hidden" name="csrf_token" value="<?= migrations_admin_e($csrfToken) ?>">
                                        <input type="hidden" name="migration" value="<?= migrations_admin_e($migration) ?>">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                name="confirm_db"
                                                class="form-control"
                                                placeholder="opiš DB: <?= migrations_admin_e($currentDbName) ?>"
                                                autocomplete="off"
                                                required
                                            >
                                            <button type="submit" class="btn btn-outline-secondary">
                                                Smazat
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
