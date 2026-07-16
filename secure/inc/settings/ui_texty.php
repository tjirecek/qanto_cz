<?php
declare(strict_types=1);

global $pdo;

require_once SEC_DIR . '/functions/fun_ui_texty.php';

$defaultLimit = (int)(sp_hodnota('limit_ui_texty-vypis') ?? 500);
if ($defaultLimit <= 0) {
    $defaultLimit = 500;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show = isset($_GET['show']) ? (int)$_GET['show'] : 0;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$notice = '';
$error = '';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string)($_POST['ui_texty_action'] ?? '');

        if ($action === 'sync') {
            $sync = ui_texty_sync_from_sources();
            $notice = 'Synchronizace hotová: vloženo ' . (int)$sync['inserted'] . ', přeskočeno ' . (int)$sync['skipped'] . ', nalezeno ' . (int)$sync['found'] . ' klíčů.';
        }

        if ($action === 'add') {
            if (ui_texty_add((string)($_POST['code'] ?? ''), (string)($_POST['cz'] ?? ''), (string)($_POST['en'] ?? ''))) {
                $notice = 'UI text byl vložen.';
                $show = 0;
            } else {
                $show = 1;
            }
        }

        if ($action === 'edit') {
            $editId = (int)($_POST['id'] ?? 0);
            $rowValid = isset($_POST['valid']) ? 1 : 0;
            if (ui_texty_edit($editId, (string)($_POST['code'] ?? ''), (string)($_POST['cz'] ?? ''), (string)($_POST['en'] ?? ''), $rowValid)) {
                $notice = 'UI text byl uložen.';
                $show = 0;
                $editId = 0;
            } else {
                $show = 2;
            }
        }
    }

    if (isset($_GET['del'])) {
        ui_texty_delete((int)$_GET['del']);
        $notice = 'UI text byl znevalidněn.';
    }

    $count = ui_texty_count($valid);
    $countAll = ui_texty_count();
    $countValid = ui_texty_count(1);
    if ($limit === 0 || $count <= $limit) {
        $limit = $count;
    }
    $loadedCount = max(0, $limit);
    $rows = ui_texty_list($limit, $valid);
} catch (Throwable $e) {
    $error = $e->getMessage();
    $count = $countAll = $countValid = $loadedCount = 0;
    $rows = [];
}

$showInactiveUrl = 'index.php?section=09&amp;page=02&amp;sec_page=10&amp;limit=9999&amp;valid=0';
$showActiveUrl = 'index.php?section=09&amp;page=02&amp;sec_page=10&amp;limit=' . (int)$defaultLimit . '&amp;valid=1';
$showAllUrl = 'index.php?section=09&amp;page=02&amp;sec_page=10&amp;limit=0&amp;valid=' . (int)$valid;
$formRow = null;
if ($show === 2 && $editId > 0) {
    $formRow = ui_texty_find($editId);
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">UI texty webu</h1>
        <div class="small text-muted">DB přepis pro <code>ui_text()</code>; <code>functions/lang.php</code> zůstává fallback.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <a href="index.php?section=09&amp;page=02&amp;sec_page=10&amp;show=1" class="btn btn-sm btn-primary shadow-sm">
            přidat UI text <i class="bi bi-plus-circle ms-1"></i>
        </a>
        <form method="post" class="d-inline">
            <input type="hidden" name="ui_texty_action" value="sync">
            <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm">
                synchronizovat z kódu <i class="bi bi-arrow-repeat ms-1"></i>
            </button>
        </form>
        <?php if ($valid === 1): ?>
            <a href="<?= $showInactiveUrl ?>" class="btn btn-sm btn-danger shadow-sm">
                zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i>
            </a>
        <?php else: ?>
            <a href="<?= $showActiveUrl ?>" class="btn btn-sm btn-outline-primary shadow-sm">
                zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($countAll, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($countValid, 0, ',', ' ') ?></span>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= ui_texty_e($error) ?></div>
<?php endif; ?>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success"><?= ui_texty_e($notice) ?></div>
<?php endif; ?>

<?php if ($show === 1 || ($show === 2 && $formRow !== null)): ?>
    <?php
    $isEdit = $show === 2 && $formRow !== null;
    $formCode = $isEdit ? (string)($formRow['code'] ?? '') : (string)($_POST['code'] ?? '');
    $formCz = $isEdit ? (string)($formRow['cz'] ?? '') : (string)($_POST['cz'] ?? '');
    $formEn = $isEdit ? (string)($formRow['en'] ?? '') : (string)($_POST['en'] ?? '');
    $formValid = $isEdit ? (int)($formRow['valid'] ?? 0) : 1;
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold <?= $isEdit ? 'text-success' : 'text-primary' ?>"><?= $isEdit ? 'Editace UI textu' : 'Přidání UI textu' ?></h6>
        </div>
        <div class="card-body">
            <form method="post" autocomplete="off">
                <input type="hidden" name="ui_texty_action" value="<?= $isEdit ? 'edit' : 'add' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$formRow['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="code" class="form-label">Kód</label>
                        <input type="text" name="code" id="code" class="form-control" value="<?= ui_texty_e($formCode) ?>" placeholder="např. nav.kariera" required>
                    </div>

                    <div class="col-12">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-6">
                                <label for="cz" class="form-label">Text CZ</label>
                                <textarea name="cz" id="cz" class="form-control" rows="3" data-translate-source="text" data-translate-format="text" required><?= ui_texty_e($formCz) ?></textarea>
                            </div>

                            <div class="col-lg-6">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <label for="en" class="form-label mb-0">Text EN</label>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".ui-texty-translate-status">
                                            <i class="bi bi-translate me-1"></i> přeložit CZ
                                        </button>
                                        <span class="small text-muted ui-texty-translate-status"></span>
                                    </div>
                                </div>
                                <textarea name="en" id="en" class="form-control mt-2" rows="3" data-translate-target="text"><?= ui_texty_e($formEn) ?></textarea>
                                <div class="form-text">EN lze předvyplnit překladem aktuální CZ hodnoty z formuláře.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <?= admin_auto_translate_checkbox($isEdit ? $formRow : null, 'ui_texty_auto_translate_en') ?>
                    </div>

                    <?php if ($isEdit): ?>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= $formValid === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="valid">valid</label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><?= $isEdit ? 'Uložit UI text' : 'Vložit UI text' ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($show === 2): ?>
    <div class="alert alert-danger">Záznam nebyl nalezen.</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">UI texty</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($loadedCount, 0, ',', ' ') ?> záznamů</span>
            <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka <code>ui_texty</code></span>
        </div>

        <?php if ($count > $loadedCount): ?>
            <a href="<?= $showAllUrl ?>" class="btn btn-sm btn-outline-secondary">
                načíst všechny záznamy (<?= number_format($count, 0, ',', ' ') ?>)
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="alert alert-info small">
            Na webu má přednost validní DB hodnota. Pokud DB hodnota chybí nebo je nevalidní, použije se fallback z <code>functions/lang.php</code> a až potom fallback přímo z volání <code>ui_text()</code>.
        </div>

        <div class="table-responsive">
            <table data-order='[[ 1, "asc" ]]' data-page-length='500' class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" id="DataTable">
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="text-filter dt-autocomplete">Kód</th>
                    <th class="text-filter">CZ</th>
                    <th class="text-filter">EN</th>
                    <th class="no-filter">Valid</th>
                    <th class="text-filter dt-autocomplete">Upraveno</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Kód</th>
                    <th>CZ</th>
                    <th>EN</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $validBadge = ((int)($row['valid'] ?? 0) === 1)
                        ? '<span class="badge text-bg-success">ANO</span>'
                        : '<span class="badge text-bg-secondary">NE</span>';
                    ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= ui_texty_e($row['code'] ?? '') ?></td>
                        <td><?= ui_texty_e(ui_texty_preview((string)($row['cz'] ?? ''))) ?></td>
                        <td><?= ui_texty_e(ui_texty_preview((string)($row['en'] ?? ''))) ?></td>
                        <td class="text-center"><?= $validBadge ?></td>
                        <td>
                            <?= ui_texty_e(format_datetime_www((string)($row['ts_u'] ?? ''))) ?>
                            <br><small class="text-muted"><?= ui_texty_e($row['user_u'] ?? '') ?></small>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-success btn-circle btn-sm" href="index.php?section=09&amp;page=02&amp;sec_page=10&amp;edit=<?= (int)$row['id'] ?>&amp;limit=<?= (int)$limit ?>&amp;show=2">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=09&amp;page=02&amp;sec_page=10&amp;del=<?= (int)$row['id'] ?>&amp;limit=<?= (int)$limit ?>" data-confirm="Opravdu znevalidnit UI text?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
