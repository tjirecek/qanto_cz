<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_akce_users.php';

global $pdo;

$csrfToken = (string)admin_session_get('rep_akce_users_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_akce_users_csrf_token', $csrfToken);
}

$notice = '';
$error = '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
$typeFilterRaw = (string)($_GET['type_filter'] ?? 'all');
$typeFilter = $typeFilterRaw === 'all' ? null : (int)$typeFilterRaw;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$types = [];
$rows = [];
$editRow = null;

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění upravovat odběratele akčních nabídek.');
        }

        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        $valid = isset($_POST['list_valid']) && (string)$_POST['list_valid'] === '0' ? 0 : 1;
        $typeFilterPost = (string)($_POST['list_type_filter'] ?? 'all');
        $typeFilter = $typeFilterPost === 'all' ? null : (int)$typeFilterPost;

        if ($action === 'save') {
            $savedId = rep_akce_users_save($pdo, $_POST, $id > 0 ? $id : null, $id <= 0);
            $notice = $id > 0 ? 'Odběratel akčních nabídek byl uložen.' : 'Odběratel akčních nabídek byl vložen.';
            $editId = $savedId;
        } elseif ($action === 'delete' && $id > 0) {
            rep_akce_users_delete($pdo, $id);
            $notice = 'Odběratel akčních nabídek byl znevalidněn.';
            $editId = 0;
        } elseif ($action === 'validate' && $id > 0) {
            rep_akce_users_set_valid($pdo, $id, 1);
            $notice = 'Odběratel akčních nabídek byl obnoven.';
            $editId = 0;
        } elseif ($action === 'end' && $id > 0) {
            rep_akce_users_end($pdo, $id);
            $notice = 'Odběr akčních nabídek byl ukončen.';
            $editId = 0;
        } elseif ($action === 'renew' && $id > 0) {
            rep_akce_users_renew($pdo, $id);
            $notice = 'Odběr akčních nabídek byl obnoven.';
            $editId = 0;
        } elseif ($action === 'import_xlsx') {
            if (!isset($_FILES['xlsx_file']) || !is_uploaded_file((string)($_FILES['xlsx_file']['tmp_name'] ?? ''))) {
                throw new RuntimeException('Vyber XLSX soubor pro import.');
            }

            $result = rep_akce_users_import_xlsx($pdo, (string)$_FILES['xlsx_file']['tmp_name']);
            $notice = 'Import dokončen: vloženo ' . (int)$result['inserted']
                . ', aktualizováno ' . (int)$result['updated']
                . ', přeskočeno ' . (int)$result['skipped'] . '.';
            if (($result['errors'] ?? []) !== []) {
                $error = 'Import obsahuje chyby: ' . implode(' | ', array_slice((array)$result['errors'], 0, 8));
            }
        }
    }

    $types = rep_akce_users_types($pdo);
    $editRow = $editId > 0 ? rep_akce_users_get($pdo, $editId) : null;
    $rows = rep_akce_users_rows($pdo, $limit, $valid, $typeFilter);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$formValues = is_array($editRow) ? $editRow : [
    'id' => 0,
    'akce_typ_id' => null,
    'legacy_akce_typ' => 0,
    'name' => '',
    'email' => '',
    'datum_od' => rep_akce_users_today(),
    'datum_do' => rep_akce_users_open_end_date(),
    'registered' => 1,
    'valid' => 1,
];
$formTypeId = (int)($formValues['akce_typ_id'] ?? 0);
$totalValid = ($pdo instanceof PDO) ? rep_akce_users_count($pdo, 1, null) : 0;
$totalInvalid = ($pdo instanceof PDO) ? rep_akce_users_count($pdo, 0, null) : 0;
$loaded = count($rows);
$typeFilterQuery = $typeFilter === null ? 'all' : (string)$typeFilter;
$queryBase = 'index.php?section=02&amp;page=02&amp;sec_page=06';
$templateUrl = BASE_URL . 'secure/functions/ajax/rep_akce_users_xlsx.php?action=template';
$validToggleUrl = $queryBase . '&amp;valid=' . ($valid === 1 ? '0' : '1') . '&amp;type_filter=' . rep_akce_users_e($typeFilterQuery);
$akceUsersUrl = static function (array $params = []): string {
    return 'index.php?' . http_build_query(array_merge([
        'section' => '02',
        'page' => '02',
        'sec_page' => '06',
    ], $params), '', '&');
};
$typeFilterLabel = 'všechny typy';
foreach ($types as $type) {
    $id = (int)$type['id'];
    if (($typeFilter === null && $id === -1) || ($typeFilter !== null && $id === $typeFilter)) {
        $typeFilterLabel = (string)$type['nazev_cz'];
    }
}
if ($typeFilter === null) {
    $typeFilterLabel = 'všechny typy';
} elseif ($typeFilter === 0) {
    $typeFilterLabel = 'Všechny akce';
}
$typeFilterOptions = [[
    'label' => 'Všechny typy',
    'value' => 'all',
    'count' => ($pdo instanceof PDO) ? rep_akce_users_count($pdo, $valid, null) : 0,
    'active' => $typeFilter === null,
    'url' => $akceUsersUrl(['valid' => $valid]),
]];
foreach ($types as $type) {
    $id = (int)$type['id'];
    $typeFilterOptions[] = [
        'label' => (string)$type['nazev_cz'],
        'value' => (string)$id,
        'count' => ($pdo instanceof PDO) ? rep_akce_users_count($pdo, $valid, $id) : 0,
        'active' => $typeFilter !== null && $typeFilter === $id,
        'url' => $akceUsersUrl(['valid' => $valid, 'type_filter' => $id]),
    ];
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Odběratelé akčních nabídek</h1>
        <div class="text-muted small">Project agenda qanto.cz: uživatelé přihlášení k odběru akčních nabídek.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <a href="<?= $queryBase ?>" class="btn btn-sm btn-primary shadow-sm"><i class="bi bi-plus-circle me-1"></i> nový odběratel</a>
        <a href="<?= rep_akce_users_e($templateUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm"><i class="bi bi-file-earmark-spreadsheet me-1"></i> šablona XLSX</a>
        <?php if ($valid === 1): ?>
            <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
        <?php else: ?>
            <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
        <?php endif; ?>
        <span class="btn btn-sm btn-success shadow-sm">validní: <?= number_format($totalValid, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-secondary shadow-sm">nevalidní: <?= number_format($totalInvalid, 0, ',', ' ') ?></span>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item"><a class="nav-link" href="index.php?section=02&amp;page=02&amp;sec_page=01"><i class="bi bi-tags me-1"></i> Akce</a></li>
    <li class="nav-item"><a class="nav-link" href="index.php?section=02&amp;page=02&amp;sec_page=02"><i class="bi bi-diagram-3 me-1"></i> Typy</a></li>
    <li class="nav-item"><a class="nav-link active" href="index.php?section=02&amp;page=02&amp;sec_page=06"><i class="bi bi-envelope-at me-1"></i> Odběratelé</a></li>
</ul>

<?php if ($notice !== ''): ?><div class="alert alert-success d-flex align-items-center" role="alert"><i class="bi bi-check-circle me-2"></i><div><?= rep_akce_users_e($notice) ?></div></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger d-flex align-items-center" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><div><?= rep_akce_users_e($error) ?></div></div><?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><?= $editRow ? 'Editace odběratele' : 'Přidání odběratele' ?></h6>
                <?php if ($editRow): ?><a href="<?= $queryBase ?>" class="btn btn-sm btn-outline-secondary">zrušit editaci</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= rep_akce_users_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($formValues['id'] ?? 0) ?>">
                    <input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                    <input type="hidden" name="list_type_filter" value="<?= rep_akce_users_e($typeFilterQuery) ?>">

                    <div class="col-md-6">
                        <label for="akce_typ_id" class="form-label">Typ akčních nabídek</label>
                        <select
                            name="akce_typ_id"
                            id="akce_typ_id"
                            class="form-select js-admin-single-picker"
                            data-picker-title="Vybrat typ akčních nabídek"
                            data-picker-description="Vyberte jeden typ odběru pro tohoto odběratele."
                            data-picker-search-placeholder="Hledat podle názvu typu…"
                        >
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int)$type['id'] ?>" <?= $formTypeId === (int)$type['id'] ? 'selected' : '' ?>><?= rep_akce_users_e($type['nazev_cz']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Jméno / popis</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= rep_akce_users_e($formValues['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?= rep_akce_users_e($formValues['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="datum_od" class="form-label">Datum od</label>
                        <input type="date" name="datum_od" id="datum_od" class="form-control" value="<?= rep_akce_users_e((string)($formValues['datum_od'] ?? rep_akce_users_today())) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="datum_do" class="form-label">Datum do</label>
                        <input type="date" name="datum_do" id="datum_do" class="form-control" value="<?= rep_akce_users_e((string)($formValues['datum_do'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-4">
                        <div class="form-check mb-2"><input type="hidden" name="registered" value="0"><input class="form-check-input" type="checkbox" name="registered" id="registered" value="1" <?= (int)($formValues['registered'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="registered">aktivní odběr</label></div>
                        <div class="form-check mb-2"><input type="hidden" name="valid" value="0"><input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= (int)($formValues['valid'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="valid">valid</label></div>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> uložit odběratele</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Import z XLSX</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">Import očekává sloupce <code>akce_typ</code>, <code>name</code>, <code>email</code>, <code>datum_od</code>, <code>datum_do</code>, <code>registered</code>, <code>valid</code>. Existující kombinace e-mail + typ se aktualizuje.</p>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= rep_akce_users_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="import_xlsx">
                    <input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                    <input type="hidden" name="list_type_filter" value="<?= rep_akce_users_e($typeFilterQuery) ?>">
                    <div class="col-12"><label for="xlsx_file" class="form-label">XLSX soubor</label><input type="file" name="xlsx_file" id="xlsx_file" class="form-control" accept=".xlsx" required></div>
                    <div class="col-12 d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> importovat</button><a href="<?= rep_akce_users_e($templateUrl) ?>" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> stáhnout šablonu</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="flex-grow-1">
            <h6 class="m-0 fw-bold text-primary d-inline">Výpis odběratelů</h6>
            <span class="text-muted small ms-2">načteno <?= (int)$loaded ?> záznamů</span>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <div class="dropdown admin-filter-dropdown" data-admin-filter-dropdown>
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-funnel me-1"></i> Typ: <?= rep_akce_users_e($typeFilterLabel) ?>
                    </button>
                    <div class="dropdown-menu admin-filter-menu p-0">
                        <div class="admin-filter-head">
                            <div class="admin-filter-title">Typ odběru akcí</div>
                            <input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat typ..." data-admin-filter-search aria-label="Hledat typ">
                        </div>
                        <div class="admin-filter-options" data-admin-filter-options>
                            <?php foreach ($typeFilterOptions as $option): ?>
                                <a class="admin-filter-option admin-filter-link <?= $option['active'] ? 'is-active' : '' ?>" href="<?= rep_akce_users_e($option['url']) ?>" data-admin-filter-item data-admin-filter-text="<?= rep_akce_users_e($option['label']) ?>">
                                    <span class="admin-filter-option-label"><?= rep_akce_users_e($option['label']) ?></span>
                                    <span class="admin-filter-count"><?= (int)$option['count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body"><div class="table-responsive">
        <table data-order='[[ 4, "desc" ], [ 0, "desc" ]]' data-page-length='500' class="table table-striped table-hover table-bordered align-middle js-datatable" width="100%" cellspacing="0">
            <thead class="table-dark"><tr><th>ID</th><th>Typ</th><th>Jméno</th><th>E-mail</th><th>Datum od</th><th>Datum do</th><th>Odběr</th><th>Valid</th><th>Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
            <tfoot class="table-light"><tr><th>ID</th><th>Typ</th><th>Jméno</th><th>E-mail</th><th>Datum od</th><th>Datum do</th><th>Odběr</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $rowTypeLabel = rep_akce_users_type_label(isset($row['akce_typ_id']) ? (int)$row['akce_typ_id'] : null, $row['legacy_akce_typ'] ?? 0, (string)($row['typ_nazev_cz'] ?? '')); ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= rep_akce_users_e($rowTypeLabel) ?></td>
                    <td><?= rep_akce_users_e($row['name'] ?? '') ?></td>
                    <td><a href="mailto:<?= rep_akce_users_e($row['email'] ?? '') ?>"><?= rep_akce_users_e($row['email'] ?? '') ?></a></td>
                    <td data-order="<?= rep_akce_users_e($row['datum_od'] ?? '') ?>"><?= rep_akce_users_e(rep_akce_users_format_date($row['datum_od'] ?? '')) ?></td>
                    <td data-order="<?= rep_akce_users_e($row['datum_do'] ?? '') ?>"><?= rep_akce_users_e(rep_akce_users_format_date($row['datum_do'] ?? '')) ?></td>
                    <td><?= rep_akce_users_registered_badge((int)($row['registered'] ?? 0)) ?></td>
                    <td><?= rep_akce_users_valid_badge((int)($row['valid'] ?? 0)) ?></td>
                    <td data-order="<?= rep_akce_users_e($row['ts_u'] ?? '') ?>"><?= rep_akce_users_updated_cell($row) ?></td>
                    <td class="text-nowrap">
                        <a href="<?= $queryBase ?>&amp;edit=<?= (int)$row['id'] ?>&amp;valid=<?= (int)$valid ?>&amp;type_filter=<?= rep_akce_users_e($typeFilterQuery) ?>" class="btn btn-success btn-sm" title="Editovat"><i class="bi bi-pencil-square"></i></a>
                        <?php if ((int)($row['registered'] ?? 0) === 1): ?>
                            <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= rep_akce_users_e($csrfToken) ?>"><input type="hidden" name="action" value="end"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>"><input type="hidden" name="list_type_filter" value="<?= rep_akce_users_e($typeFilterQuery) ?>"><button type="submit" class="btn btn-warning btn-sm" title="Ukončit odběr"><i class="bi bi-pause-circle"></i></button></form>
                        <?php else: ?>
                            <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= rep_akce_users_e($csrfToken) ?>"><input type="hidden" name="action" value="renew"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>"><input type="hidden" name="list_type_filter" value="<?= rep_akce_users_e($typeFilterQuery) ?>"><button type="submit" class="btn btn-primary btn-sm" title="Obnovit odběr"><i class="bi bi-play-circle"></i></button></form>
                        <?php endif; ?>
                        <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= rep_akce_users_e($csrfToken) ?>"><input type="hidden" name="action" value="<?= (int)($row['valid'] ?? 0) === 1 ? 'delete' : 'validate' ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>"><input type="hidden" name="list_type_filter" value="<?= rep_akce_users_e($typeFilterQuery) ?>"><button type="submit" class="btn btn-<?= (int)($row['valid'] ?? 0) === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)($row['valid'] ?? 0) === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)($row['valid'] ?? 0) === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>
