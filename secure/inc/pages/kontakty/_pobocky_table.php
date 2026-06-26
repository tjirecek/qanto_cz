<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_pobocky.php';

global $pdo;

$type = pobocky_normalize_type((string)($pobockyType ?? 'prodejna'));
$typeLabel = pobocky_type_label($type);
$pageTitle = (string)($pobockyTitle ?? ('Kontakty: ' . $typeLabel));

$defaultLimit = 100;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show = isset($_GET['show']) ? (int)$_GET['show'] : 0;
$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$del = isset($_GET['del']) ? (int)$_GET['del'] : 0;

if (!in_array($valid, [0, 1], true)) {
    $valid = 1;
}

$routes = [
    'prodejna' => pobocky_page_url('prodejna'),
    'market' => pobocky_page_url('market'),
    'velkoobchod' => pobocky_page_url('velkoobchod'),
];

$counts = [
    'total' => 0,
    'valid_total' => 0,
    'market' => 0,
    'market_valid' => 0,
    'prodejna' => 0,
    'prodejna_valid' => 0,
    'velkoobchod' => 0,
    'velkoobchod_valid' => 0,
];

$messages = [];
$currentCount = 0;

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    pobocky_prepare_tables($pdo);

    if ($del > 0) {
        pobocky_delete($pdo, $del, $type);
        $messages[] = [
            'type' => 'success',
            'text' => 'Záznam byl smazán.',
        ];
    }

    $counts = pobocky_table_counts($pdo);
    $currentCount = pobocky_count($pdo, $type, $valid);
} catch (Throwable $e) {
    $messages[] = [
        'type' => 'danger',
        'text' => 'Nepodarilo se pripravit sekci pobocky: ' . $e->getMessage(),
    ];
}

if ($limit === 0 || $currentCount <= $limit) {
    $limit = $currentCount;
}

if ($limit < 0) {
    $limit = $defaultLimit;
}

$currentTotal = (int)($counts[$type] ?? 0);
$currentValid = (int)($counts[$type . '_valid'] ?? 0);
$loadedCount = ($limit === 0 && $currentCount > 0) ? $currentCount : max(0, $limit);
$showInactiveUrl = pobocky_page_url($type, ['limit' => 9999, 'valid' => 0]);
$showActiveUrl = pobocky_page_url($type, ['limit' => $defaultLimit, 'valid' => 1]);
$showAllUrl = pobocky_page_url($type, ['limit' => 0, 'valid' => $valid]);
$addUrl = pobocky_page_url($type, ['show' => 1, 'limit' => $loadedCount, 'valid' => $valid]);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars($addUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat pobočku <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= htmlspecialchars($showInactiveUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                    zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($showActiveUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-outline-primary shadow-sm d-none d-sm-inline-block">
                    zobrazit aktivní záznamy <i class="bi bi-arrow-clockwise ms-1"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format((int)$counts['total'], 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">aktivní: <?= number_format((int)$counts['valid_total'], 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-primary shadow-sm"><?= htmlspecialchars($typeLabel, ENT_QUOTES) ?>: <?= number_format($currentTotal, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($currentValid, 0, ',', ' ') ?></span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= htmlspecialchars($message['type'], ENT_QUOTES) ?> mb-3">
        <?= htmlspecialchars($message['text'], ENT_QUOTES) ?>
    </div>
<?php endforeach; ?>

<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání pobočky</h6>
        </div>

        <?php if ($show === 11): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i> Záznam byl vložen
                </div>
            </div>
        <?php else: ?>
            <?php include __DIR__ . '/_pobocky_add.php'; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace pobočky</h6>
        </div>

        <?php if ($show === 21): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i> Záznam byl uložen
                </div>
            </div>
        <?php else: ?>
            <?php include __DIR__ . '/_pobocky_edit.php'; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= htmlspecialchars($typeLabel, ENT_QUOTES) ?></h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($loadedCount, 0, ',', ' ') ?> záznamů</span>
            <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `pobocky` filtrovaná podle `typ`</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($routes['prodejna'], ENT_QUOTES) ?>" class="btn btn-sm <?= $type === 'prodejna' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Prodejny
            </a>
            <a href="<?= htmlspecialchars($routes['market'], ENT_QUOTES) ?>" class="btn btn-sm <?= $type === 'market' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Markety
            </a>
            <a href="<?= htmlspecialchars($routes['velkoobchod'], ENT_QUOTES) ?>" class="btn btn-sm <?= $type === 'velkoobchod' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Velkoobchody
            </a>

            <?php if ($currentCount > $loadedCount): ?>
                <a href="<?= htmlspecialchars($showAllUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-outline-secondary">
                    načíst všechny záznamy (<?= number_format($currentCount, 0, ',', ' ') ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100"
                id="DataTablePobocky_<?= htmlspecialchars($type, ENT_QUOTES) ?>"
                data-order='[[ 1, "asc" ], [ 0, "desc" ]]'
                data-page-length='100'
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="text-filter autocomplete">Pořadí</th>
                    <th class="text-filter autocomplete">Středisko</th>
                    <th class="text-filter autocomplete">Název CZ</th>
                    <th class="text-filter autocomplete">Název EN</th>
                    <th class="text-filter autocomplete">Mobil</th>
                    <th class="text-filter autocomplete">E-mail</th>
                    <th class="text-filter">Adresa</th>
                    <th class="text-filter autocomplete">Vedoucí</th>
                    <th class="text-filter autocomplete">Galerie</th>
                    <th class="no-filter">Valid</th>
                    <th class="text-filter autocomplete">Upraveno</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Pořadí</th>
                    <th>Středisko</th>
                    <th>Název CZ</th>
                    <th>Název EN</th>
                    <th>Mobil</th>
                    <th>E-mail</th>
                    <th>Adresa</th>
                    <th>Vedoucí</th>
                    <th>Galerie</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php
                if ($pdo instanceof PDO) {
                    pobocky_vypis($pdo, $loadedCount, $valid, $type);
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
