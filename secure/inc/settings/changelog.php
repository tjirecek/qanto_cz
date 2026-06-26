<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_changelog.php';

function changelog_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$messages = [];
$show = (int)($_GET['show'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);
$form = changelog_default();
$editing = false;
$tableExists = changelog_table_exists();

if (!$tableExists) {
    $messages[] = [
        'type' => 'warning',
        'text' => 'Tabulka changelog zatím neexistuje. Nejdříve spusť odpovídající aktivní migraci v secure/sql/.',
    ];
} else {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        $action = trim((string)($_POST['action'] ?? ''));

        if ($action === 'archive') {
            $archiveId = (int)($_POST['id'] ?? 0);
            if ($archiveId > 0) {
                try {
                    changelog_archive($archiveId);
                    $messages[] = ['type' => 'success', 'text' => 'Změna byla skryta z aktivní evidence.'];
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Změnu se nepodařilo skrýt: ' . $e->getMessage()];
                }
            }
        }

        if ($action === 'create' || $action === 'update') {
            $postId = (int)($_POST['id'] ?? 0);
            $base = changelog_default();
            if ($action === 'update' && $postId > 0) {
                $existing = changelog_fetch($postId);
                if ($existing !== null) {
                    $base = array_merge($base, $existing);
                }
            }

            $form = changelog_from_request($_POST, $base);
            [$errors, $normalized] = changelog_validate($form);
            $form = $normalized;

            if ($errors) {
                foreach ($errors as $error) {
                    $messages[] = ['type' => 'warning', 'text' => $error];
                }
                $show = $action === 'update' ? 2 : 1;
                $editing = $action === 'update';
                $editId = $postId;
            } else {
                try {
                    if ($action === 'create') {
                        changelog_create($form);
                        $messages[] = ['type' => 'success', 'text' => 'Změna byla zaevidována.'];
                        $form = changelog_default();
                        $show = 0;
                    } else {
                        changelog_update($postId, $form);
                        $messages[] = ['type' => 'success', 'text' => 'Změna byla uložena.'];
                        $existing = changelog_fetch($postId);
                        if ($existing !== null) {
                            $form = array_merge(changelog_default(), $existing);
                            $editing = true;
                            $show = 2;
                            $editId = $postId;
                        }
                    }
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Změnu se nepodařilo uložit: ' . $e->getMessage()];
                    $show = $action === 'update' ? 2 : 1;
                    $editing = $action === 'update';
                    $editId = $postId;
                }
            }
        }
    }

    if ($show === 2 && $editId > 0 && !$editing) {
        $existing = changelog_fetch($editId);
        if ($existing === null) {
            $messages[] = ['type' => 'warning', 'text' => 'Požadovaná změna neexistuje.'];
            $show = 0;
        } else {
            $form = array_merge(changelog_default(), $existing);
            $editing = true;
        }
    }
}

$rows = $tableExists ? changelog_list(false) : [];
$statuses = changelog_statuses();
$categories = changelog_categories();
$currentYear = (int)date('Y');
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">ChangeLog</h1>

    <div class="d-flex flex-wrap gap-2">
        <?php if ($tableExists): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=07&amp;show=1"
               class="btn btn-sm btn-primary shadow-sm">
                Přidat změnu <i class="bi bi-plus-circle"></i>
            </a>
        <?php endif; ?>
        <span class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">
            aktivních: <?= (int)count($rows) ?>
        </span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= changelog_e((string)$message['type']) ?> py-2 mb-2">
        <?= changelog_e((string)$message['text']) ?>
    </div>
<?php endforeach; ?>

<?php if ($tableExists && ($show === 1 || $show === 2)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">
                <?= $editing ? 'Editace změny' : 'Přidání změny' ?>
            </h6>
        </div>

        <div class="card-body">
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <label for="title" class="form-label">Název změny</label>
                        <input type="text" name="title" id="title" class="form-control" maxlength="180"
                               value="<?= changelog_e((string)$form['title']) ?>" required>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="category" class="form-label">Kategorie</label>
                        <select name="category" id="category" class="form-select" required>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= changelog_e($value) ?>" <?= (string)$form['category'] === $value ? 'selected' : '' ?>>
                                    <?= changelog_e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="status" class="form-label">Stav</label>
                        <select name="status" id="status" class="form-select" required>
                            <?php foreach ($statuses as $value => $meta): ?>
                                <option value="<?= changelog_e($value) ?>" <?= (string)$form['status'] === $value ? 'selected' : '' ?>>
                                    <?= changelog_e((string)$meta['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Popis</label>
                        <textarea name="description" id="description" class="form-control" rows="4"><?= changelog_e((string)$form['description']) ?></textarea>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="recorded_on" class="form-label">Zaevidováno</label>
                        <input type="date" name="recorded_on" id="recorded_on" class="form-control"
                               value="<?= changelog_e((string)$form['recorded_on']) ?>" required>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="planned_month" class="form-label">Plán měsíc</label>
                        <select name="planned_month" id="planned_month" class="form-select">
                            <option value="">bez termínu</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= (int)($form['planned_month'] ?? 0) === $m ? 'selected' : '' ?>>
                                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="planned_year" class="form-label">Plán rok</label>
                        <select name="planned_year" id="planned_year" class="form-select">
                            <option value="">bez termínu</option>
                            <?php for ($y = $currentYear - 1; $y <= $currentYear + 4; $y++): ?>
                                <option value="<?= $y ?>" <?= (int)($form['planned_year'] ?? 0) === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="done_on" class="form-label">Hotovo</label>
                        <input type="date" name="done_on" id="done_on" class="form-control"
                               value="<?= changelog_e((string)($form['done_on'] ?? '')) ?>">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="priority" class="form-label">Pořadí</label>
                        <input type="number" min="0" max="255" step="1" name="priority" id="priority" class="form-control"
                               value="<?= (int)$form['priority'] ?>">
                    </div>

                    <div class="col-6 col-lg-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="active_l" id="active_l" value="1"
                                <?= (int)($form['active_l'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active_l">aktivní</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing ? 'Uložit změnu' : 'Vložit změnu' ?>
                    </button>
                    <a href="index.php?section=02&amp;page=02&amp;sec_page=07" class="btn btn-outline-secondary">Zpět na přehled</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($tableExists): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Evidence změn</h6>
            <span class="d-none d-sm-inline-block ms-2 text-muted">plánované a nasazené změny</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm align-middle js-datatable" data-order='[[ 5, "asc" ], [ 0, "desc" ]]' data-page-length='50'>
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="text-filter">ID</th>
                        <th class="text-filter">Název</th>
                        <th class="text-filter">Kategorie</th>
                        <th class="text-filter">Stav</th>
                        <th data-type="date">Zaevidováno</th>
                        <th class="text-filter">Plán</th>
                        <th data-type="date">Hotovo</th>
                        <th>Pořadí</th>
                        <th class="no-sort no-filter text-end">Akce</th>
                    </tr>
                    </thead>
                    <tfoot class="table-light">
                    <tr>
                        <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $status = (string)($row['status'] ?? '');
                        $category = (string)($row['category'] ?? '');
                        ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= changelog_e((string)$row['title']) ?></div>
                                <?php if (trim((string)($row['description'] ?? '')) !== ''): ?>
                                    <div class="small text-muted"><?= nl2br(changelog_e((string)$row['description'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= changelog_e(changelog_category_label($category)) ?></td>
                            <td>
                                <span class="badge <?= changelog_e(changelog_status_badge($status)) ?>">
                                    <?= changelog_e(changelog_status_label($status)) ?>
                                </span>
                            </td>
                            <td><?= changelog_e((string)format_date_www((string)($row['recorded_on'] ?? ''))) ?></td>
                            <td><?= changelog_e(changelog_planned_text($row)) ?></td>
                            <td><?= changelog_e((string)format_date_www((string)($row['done_on'] ?? ''))) ?></td>
                            <td><?= (int)($row['priority'] ?? 0) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a class="btn btn-outline-primary" href="index.php?section=02&amp;page=02&amp;sec_page=07&amp;show=2&amp;edit=<?= (int)$row['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Skrýt tuto změnu z aktivní evidence?');">
                                        <input type="hidden" name="action" value="archive">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-eye-slash"></i></button>
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
<?php endif; ?>
