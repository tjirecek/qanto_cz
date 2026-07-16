<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_napiste_nam.php';

global $sec_page;

$activeSubpage = (string)($sec_page ?? '01') === '02' ? '02' : '01';
$messages = [];
$errors = [];
$defaultLimit = 500;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
if ($limit < 0) {
    $limit = $defaultLimit;
}
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = $valid === 0 ? 0 : 1;
$baseParams = ['section' => '01', 'page' => '05', 'sec_page' => $activeSubpage];
$baseUrl = 'index.php?' . http_build_query($baseParams, '', '&amp;');
$editCategoryId = isset($_GET['edit_category']) ? max(0, (int)$_GET['edit_category']) : 0;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_category') {
            $id = isset($_POST['id']) && (int)$_POST['id'] > 0 ? (int)$_POST['id'] : null;
            napiste_nam_category_save($_POST, $id);
            $messages[] = $id === null ? 'Kategorie byla vytvořena.' : 'Kategorie byla uložena.';
            $editCategoryId = 0;
            $activeSubpage = '02';
            $baseUrl = 'index.php?section=01&amp;page=05&amp;sec_page=02';
        }
    }

    if (isset($_GET['delete_category'])) {
        napiste_nam_category_delete((int)$_GET['delete_category']);
        $messages[] = 'Kategorie byla smazána.';
        $activeSubpage = '02';
        $baseUrl = 'index.php?section=01&amp;page=05&amp;sec_page=02';
    }

    if (isset($_GET['delete_message'])) {
        napiste_nam_message_delete((int)$_GET['delete_message']);
        $messages[] = 'Zpráva byla smazána.';
        $activeSubpage = '01';
        $baseUrl = 'index.php?section=01&amp;page=05&amp;sec_page=01';
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$categoryCount = napiste_nam_category_count($valid);
$categoryLimit = ($limit === 0 || $categoryCount <= $limit) ? $categoryCount : $limit;
$categories = $activeSubpage === '02' ? napiste_nam_categories_all($valid, $categoryLimit) : [];
$editCategory = $editCategoryId > 0 ? napiste_nam_category_get($editCategoryId) : null;
$categoryOrderValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['poradi'] ?? '')
    : (string)($editCategory['poradi'] ?? napiste_nam_category_next_order());

$messageCount = napiste_nam_message_count($valid);
$messageLimit = ($limit === 0 || $messageCount <= $limit) ? $messageCount : $limit;
$messageRows = $activeSubpage === '01' ? napiste_nam_messages_all($valid, $messageLimit) : [];
$validToggleParams = [
    'section' => '01',
    'page' => '05',
    'sec_page' => $activeSubpage,
    'valid' => $valid === 1 ? '0' : '1',
];
if ($limit !== $defaultLimit) {
    $validToggleParams['limit'] = (string)$limit;
}
$validToggleUrl = 'index.php?' . http_build_query($validToggleParams, '', '&amp;');
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Napište nám</h1>
        <div class="text-muted small">Shared agenda pro kategorie kontaktního formuláře a historické přijaté zprávy.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
            <?php else: ?>
                <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
            <?php endif; ?>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">zprávy: <?= number_format(napiste_nam_message_count(), 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">kategorie: <?= number_format(napiste_nam_category_count(), 0, ',', ' ') ?></span>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '01' ? 'active' : '' ?>" href="index.php?section=01&amp;page=05&amp;sec_page=01">
            <i class="bi bi-inbox me-1"></i> Výpis zpráv
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '02' ? 'active' : '' ?>" href="index.php?section=01&amp;page=05&amp;sec_page=02">
            <i class="bi bi-tags me-1"></i> Kategorie
        </a>
    </li>
</ul>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i><?= napiste_nam_e($message) ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i><?= napiste_nam_e($error) ?></div>
<?php endforeach; ?>

<?php if ($activeSubpage === '02'): ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= $editCategory ? 'Upravit kategorii' : 'Přidání kategorie' ?></h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="save_category">
                        <input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label" for="napiste_poradi">Pořadí</label>
                            <input type="number" class="form-control" name="poradi" id="napiste_poradi" value="<?= napiste_nam_e($categoryOrderValue) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="napiste_nazev_cz">Název CZ</label>
                            <input type="text" class="form-control" name="nazev_cz" id="napiste_nazev_cz" required value="<?= napiste_nam_e($editCategory['nazev_cz'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="napiste_nazev_en">Název EN</label>
                            <input type="text" class="form-control" name="nazev_en" id="napiste_nazev_en" value="<?= napiste_nam_e($editCategory['nazev_en'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="napiste_email_to">E-mail příjemce</label>
                            <textarea class="form-control" name="email_to" id="napiste_email_to" rows="2" required><?= napiste_nam_e($editCategory['email_to'] ?? '') ?></textarea>
                            <div class="form-text">Více e-mailů odděl čárkou, středníkem nebo novým řádkem.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="napiste_email_copy">E-mail kopie</label>
                            <textarea class="form-control" name="email_copy" id="napiste_email_copy" rows="2"><?= napiste_nam_e($editCategory['email_copy'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="napiste_type">Typ</label>
                            <select class="form-select" name="type" id="napiste_type">
                                <?php $selectedType = (int)($editCategory['type'] ?? 1); ?>
                                <option value="1" <?= $selectedType === 1 ? 'selected' : '' ?>>veřejný formulář</option>
                                <option value="0" <?= $selectedType === 0 ? 'selected' : '' ?>>interní / ostatní</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6 form-check form-switch ms-2">
                                <input class="form-check-input" type="checkbox" name="visible" id="napiste_visible" value="1" <?= (int)($editCategory['visible'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="napiste_visible">viditelné na webu</label>
                            </div>
                            <div class="col-md-5 form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="valid" id="napiste_valid" value="1" <?= (int)($editCategory['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="napiste_valid">valid</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <?= admin_auto_translate_checkbox($editCategory ?? null, 'napiste_auto_translate_en') ?>
                        </div>

                        <?php if ($editCategory): ?>
                            <div class="small text-muted mb-3">
                                Upraveno: <?= napiste_nam_e(format_datetime_www((string)($editCategory['ts_u'] ?? ''))) ?>;
                                Upravil: <?= napiste_nam_e($editCategory['user_u'] ?? '') ?>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Uložit kategorii</button>
                        <?php if ($editCategory): ?>
                            <a class="btn btn-outline-secondary" href="index.php?section=01&amp;page=05&amp;sec_page=02">Zrušit editaci</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline">Kategorie</h6>
                    <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($categoryLimit, 0, ',', ' ') ?> záznamů</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "asc" ], [ 0, "asc" ]]' data-page-length="500">
                            <thead class="table-dark align-middle">
                            <tr>
                                <th class="no-filter">ID</th>
                                <th class="text-filter dt-autocomplete">Pořadí</th>
                                <th class="text-filter dt-autocomplete">Název</th>
                                <th class="text-filter dt-autocomplete">E-mail</th>
                                <th class="text-filter dt-autocomplete">Kopie</th>
                                <th class="select-filter">Visible</th>
                                <th class="select-filter">Valid</th>
                                <th class="no-sort no-filter">Upravit</th>
                                <th class="no-sort no-filter">Smazat</th>
                            </tr>
                            </thead>
                            <tfoot class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Pořadí</th>
                                <th>Název</th>
                                <th>E-mail</th>
                                <th>Kopie</th>
                                <th>Visible</th>
                                <th>Valid</th>
                                <th>Upravit</th>
                                <th>Smazat</th>
                            </tr>
                            </tfoot>
                            <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= (int)$category['id'] ?></td>
                                    <td><?= (int)($category['poradi'] ?? 0) ?></td>
                                    <td class="fw-semibold"><?= napiste_nam_e($category['nazev_cz'] ?? '') ?></td>
                                    <td><?= napiste_nam_e($category['email_to'] ?? '') ?></td>
                                    <td><?= napiste_nam_e($category['email_copy'] ?? '') ?></td>
                                    <td class="text-center" data-search="<?= (int)($category['visible'] ?? 0) === 1 ? 'ANO' : 'NE' ?>">
                                        <span class="badge text-bg-<?= (int)($category['visible'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= (int)($category['visible'] ?? 0) === 1 ? 'ANO' : 'NE' ?></span>
                                    </td>
                                    <td class="text-center" data-search="<?= (int)($category['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?>">
                                        <span class="badge text-bg-<?= (int)($category['valid'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= (int)($category['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-success btn-sm" href="index.php?section=01&amp;page=05&amp;sec_page=02&amp;edit_category=<?= (int)$category['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Upravit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-danger btn-sm" href="index.php?section=01&amp;page=05&amp;sec_page=02&amp;delete_category=<?= (int)$category['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Smazat" data-confirm="Opravdu smazat kategorii?">
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
        </div>
    </div>
<?php else: ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Výpis zpráv</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($messageLimit, 0, ',', ' ') ?> záznamů</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "desc" ]]' data-page-length="500">
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="no-filter">ID</th>
                        <th class="text-filter">Datum</th>
                        <th class="text-filter dt-autocomplete">Kategorie</th>
                        <th class="text-filter dt-autocomplete">Jméno</th>
                        <th class="text-filter">E-mail</th>
                        <th class="text-filter">Telefon</th>
                        <th class="text-filter">Text</th>
                        <th class="select-filter">Valid</th>
                        <th class="no-sort no-filter">Smazat</th>
                    </tr>
                    </thead>
                    <tfoot class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Kategorie</th>
                        <th>Jméno</th>
                        <th>E-mail</th>
                        <th>Telefon</th>
                        <th>Text</th>
                        <th>Valid</th>
                        <th>Smazat</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php foreach ($messageRows as $row): ?>
                        <?php
                        $text = trim((string)($row['text'] ?? ''));
                        $textShort = mb_strlen($text, 'UTF-8') > 100 ? mb_substr($text, 0, 100, 'UTF-8') . '…' : $text;
                        $messageModalId = 'napisteMessageModal' . (int)$row['id'];
                        ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td class="text-nowrap" data-order="<?= napiste_nam_e($row['datum'] ?? '') ?>"><?= napiste_nam_e($row['datum'] ?? '') ?></td>
                            <td><?= napiste_nam_e($row['kategorie_nazev'] ?? '') ?></td>
                            <td><?= napiste_nam_e($row['name'] ?? '') ?></td>
                            <td><?= napiste_nam_e($row['email'] ?? '') ?></td>
                            <td><?= napiste_nam_e($row['telefon'] ?? '') ?></td>
                            <td>
                                <?= nl2br(napiste_nam_e($textShort)) ?>
                                <?php if ($text !== ''): ?>
                                    <div>
                                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#<?= napiste_nam_e($messageModalId) ?>">
                                            Zobrazit celé
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" data-search="<?= (int)($row['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?>">
                                <span class="badge text-bg-<?= (int)($row['valid'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= (int)($row['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?></span>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-danger btn-sm" href="index.php?section=01&amp;page=05&amp;sec_page=01&amp;delete_message=<?= (int)$row['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Smazat" data-confirm="Opravdu smazat zprávu?">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($messageRows as $row): ?>
                <?php
                $text = trim((string)($row['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $messageModalId = 'napisteMessageModal' . (int)$row['id'];
                ?>
                <div class="modal fade" id="<?= napiste_nam_e($messageModalId) ?>" tabindex="-1" aria-labelledby="<?= napiste_nam_e($messageModalId) ?>Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title" id="<?= napiste_nam_e($messageModalId) ?>Label">Zpráva #<?= (int)$row['id'] ?></h5>
                                    <div class="small text-muted">
                                        <?= napiste_nam_e($row['datum'] ?? '') ?>
                                        <?php if ((string)($row['kategorie_nazev'] ?? '') !== ''): ?>
                                            · <?= napiste_nam_e($row['kategorie_nazev'] ?? '') ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 mb-3 small">
                                    <div class="col-md-4">
                                        <div class="text-muted">Jméno</div>
                                        <div class="fw-semibold text-break"><?= napiste_nam_e($row['name'] ?? '') ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted">E-mail</div>
                                        <div class="fw-semibold text-break"><?= napiste_nam_e($row['email'] ?? '') ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted">Telefon</div>
                                        <div class="fw-semibold text-break"><?= napiste_nam_e($row['telefon'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div class="border rounded bg-light p-3 text-break">
                                    <?= nl2br(napiste_nam_e($text)) ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
