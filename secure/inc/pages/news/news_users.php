<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_news_users.php';

$csrfToken = (string)admin_session_get('news_users_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('news_users_csrf_token', $csrfToken);
}

$notice = '';
$error = '';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_news-users');
if ($limit <= 0) {
    $limit = 500;
}
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }

        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'save') {
            $savedId = news_users_save($_POST, $id > 0 ? $id : null, $id <= 0);
            $notice = $id > 0 ? 'Uživatel newsletteru byl uložen.' : 'Uživatel newsletteru byl vložen.';
            $editId = $savedId;
        } elseif ($action === 'delete' && $id > 0) {
            news_users_delete($id);
            $notice = 'Uživatel newsletteru byl smazán.';
            $editId = 0;
        } elseif ($action === 'end' && $id > 0) {
            news_users_end($id);
            $notice = 'Odběr newsletteru byl ukončen.';
            $editId = 0;
        } elseif ($action === 'renew' && $id > 0) {
            news_users_renew($id);
            $notice = 'Odběr newsletteru byl obnoven.';
            $editId = 0;
        } elseif ($action === 'import_xlsx') {
            if (!isset($_FILES['xlsx_file']) || !is_uploaded_file((string)($_FILES['xlsx_file']['tmp_name'] ?? ''))) {
                throw new RuntimeException('Vyber XLSX soubor pro import.');
            }

            $result = news_users_import_xlsx((string)$_FILES['xlsx_file']['tmp_name']);
            $notice = 'Import dokončen: vloženo ' . (int)$result['inserted']
                . ', aktualizováno ' . (int)$result['updated']
                . ', přeskočeno ' . (int)$result['skipped'] . '.';
            if (($result['errors'] ?? []) !== []) {
                $error = 'Import obsahuje chyby: ' . implode(' | ', array_slice((array)$result['errors'], 0, 8));
            }
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$editRow = $editId > 0 ? news_users_get($editId) : null;
$formValues = is_array($editRow) ? $editRow : [
    'id' => 0,
    'name' => '',
    'email' => '',
    'datum_od' => news_users_today(),
    'datum_do' => news_users_zero_date(),
    'registered' => 1,
    'valid' => 1,
];

$totalValid = news_users_count($valid);
$rows = news_users_rows($limit, $valid);
$loaded = count($rows);
$queryBase = 'index.php?section=01&amp;page=01&amp;sec_page=05';
$templateUrl = BASE_URL . 'secure/functions/ajax/news_users_xlsx.php?action=template';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Uživatelé newsletteru</h1>
        <div class="text-muted small">Mailing novinek do schránek přihlášených uživatelů.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <a href="<?= $queryBase ?>" class="btn btn-sm btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> nový uživatel
        </a>
        <a href="<?= news_users_e($templateUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> šablona XLSX
        </a>
        <?php if ($valid === 1): ?>
            <a href="<?= $queryBase ?>&amp;valid=0&amp;limit=500" class="btn btn-sm btn-outline-danger shadow-sm">
                <i class="bi bi-circle-half me-1"></i> nevalidní záznamy
            </a>
        <?php else: ?>
            <a href="<?= $queryBase ?>&amp;valid=1&amp;limit=500" class="btn btn-sm btn-outline-success shadow-sm">
                <i class="bi bi-check-circle me-1"></i> validní záznamy
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= news_users_e($notice) ?></div>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div><?= news_users_e($error) ?></div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><?= $editRow ? 'Editace uživatele newsletteru' : 'Přidání uživatele newsletteru' ?></h6>
                <?php if ($editRow): ?>
                    <a href="<?= $queryBase ?>" class="btn btn-sm btn-outline-secondary">zrušit editaci</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= news_users_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($formValues['id'] ?? 0) ?>">

                    <div class="col-md-6">
                        <label for="name" class="form-label">Jméno / popis</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= news_users_e($formValues['name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?= news_users_e($formValues['email'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="datum_od" class="form-label">Datum od</label>
                        <input type="date" name="datum_od" id="datum_od" class="form-control" value="<?= news_users_e((string)($formValues['datum_od'] ?? news_users_today())) ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="datum_do" class="form-label">Datum do</label>
                        <input type="date" name="datum_do" id="datum_do" class="form-control" value="<?= news_users_e((string)($formValues['datum_do'] ?? '') === '0000-00-00' ? '' : (string)($formValues['datum_do'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-end gap-4">
                        <div class="form-check mb-2">
                            <input type="hidden" name="registered" value="0">
                            <input class="form-check-input" type="checkbox" name="registered" id="registered" value="1" <?= (int)($formValues['registered'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="registered">aktivní odběr</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="hidden" name="valid" value="0">
                            <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= (int)($formValues['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="valid">valid</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> uložit uživatele
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Import z XLSX</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Import očekává sloupce <code>name</code>, <code>email</code>, <code>datum_od</code>, <code>datum_do</code>, <code>registered</code>, <code>valid</code>. Existující e-mail se aktualizuje.</p>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= news_users_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="import_xlsx">
                    <div class="col-12">
                        <label for="xlsx_file" class="form-label">XLSX soubor</label>
                        <input type="file" name="xlsx_file" id="xlsx_file" class="form-control" accept=".xlsx" required>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> importovat
                        </button>
                        <a href="<?= news_users_e($templateUrl) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> stáhnout šablonu
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-inline">Výpis uživatelů newsletteru</h6>
            <span class="text-muted small ms-2">načteno <?= (int)$loaded ?> z <?= (int)$totalValid ?> záznamů</span>
        </div>
        <?php if ($loaded < $totalValid): ?>
            <a href="<?= $queryBase ?>&amp;valid=<?= (int)$valid ?>&amp;limit=0" class="btn btn-sm btn-outline-primary shadow-sm">
                načíst vše (<?= (int)$totalValid ?>)
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table data-order='[[ 3, "desc" ], [ 0, "desc" ]]'
                   data-page-length='500'
                   class="table table-striped table-hover table-bordered align-middle"
                   id="dataTable" width="100%" cellspacing="0">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>E-mail</th>
                    <th>Datum od</th>
                    <th>Datum do</th>
                    <th>Odběr</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th class="news-users-actions-col">Akce</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>E-mail</th>
                    <th>Datum od</th>
                    <th>Datum do</th>
                    <th>Odběr</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th class="news-users-actions-col">Akce</th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= news_users_e($row['name'] ?? '') ?></td>
                        <td><a href="mailto:<?= news_users_e($row['email'] ?? '') ?>"><?= news_users_e($row['email'] ?? '') ?></a></td>
                        <td><?= news_users_e(news_users_format_date($row['datum_od'] ?? '')) ?></td>
                        <td><?= news_users_e(news_users_format_date($row['datum_do'] ?? '')) ?></td>
                        <td><?= news_users_registered_badge((int)($row['registered'] ?? 0)) ?></td>
                        <td><?= news_users_valid_badge((int)($row['valid'] ?? 0)) ?></td>
                        <td><?= news_users_e($row['ts_u'] ?? '') ?></td>
                        <td class="news-users-actions-col">
                            <div class="news-users-actions">
                                <a href="<?= $queryBase ?>&amp;edit=<?= (int)$row['id'] ?>&amp;valid=<?= (int)$valid ?>&amp;limit=<?= (int)$limit ?>" class="btn btn-success btn-circle btn-sm" title="Editovat">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int)($row['registered'] ?? 0) === 1): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= news_users_e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="end">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-warning btn-circle btn-sm" title="Ukončit odběr">
                                            <i class="bi bi-pause-circle"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= news_users_e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="renew">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-circle btn-sm" title="Obnovit odběr">
                                            <i class="bi bi-play-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= news_users_e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-circle btn-sm" title="Smazat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
