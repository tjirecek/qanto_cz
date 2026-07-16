<?php
declare(strict_types=1);

include "functions/fun_news.php";
global $pdo;

$messages = [];
$errors = [];
$baseUrl = 'index.php?section=01&amp;page=01&amp;sec_page=03';
$defaultLimit = (int)sp_hodnota('limit_news-typ');
if ($defaultLimit <= 0) {
    $defaultLimit = 500;
}
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
if ($limit < 0) {
    $limit = $defaultLimit;
}
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = $valid === 0 ? 0 : 1;
$editTypeId = isset($_GET['edit_type']) ? (int)$_GET['edit_type'] : 0;
$editTypeId = $editTypeId > 0 ? $editTypeId : 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_type') {
            $id = isset($_POST['id']) && (int)$_POST['id'] > 0 ? (int)$_POST['id'] : null;
            news_typ_save($_POST, $id);
            $messages[] = $id === null ? 'Typ novinek byl vytvořen.' : 'Typ novinek byl uložen.';
            $editTypeId = 0;
        }
    }

    if (isset($_GET['delete_type'])) {
        news_typ_delete((int)$_GET['delete_type']);
        $messages[] = 'Typ novinek byl smazán.';
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$typeCount = news_typ_count($valid);
$typeLimit = ($limit === 0 || $typeCount <= $limit) ? $typeCount : $limit;
$types = news_typ_all($valid, $typeLimit);
$editType = $editTypeId > 0 ? news_typ_get($editTypeId) : null;

$toggleParams = [
    'section' => '01',
    'page' => '01',
    'sec_page' => '03',
];
if ($limit !== $defaultLimit) {
    $toggleParams['limit'] = (string)$limit;
}
$toggleParams['valid'] = $valid === 1 ? '0' : '1';
$validToggleUrl = 'index.php?' . http_build_query($toggleParams, '', '&amp;');

$typeTotal = news_typ_count();
$typeValidTotal = news_typ_count(1);
$typeOrderValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['poradi'] ?? '')
    : (string)($editType['poradi'] ?? news_typ_next_order());
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Typy novinek</h1>

    <div class="d-flex flex-wrap gap-2">
        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $validToggleUrl ?>" class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                    zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
                </a>
            <?php else: ?>
                <a href="<?= $validToggleUrl ?>" class="d-none d-sm-inline-block btn btn-sm btn-outline-primary shadow-sm">
                    zobrazit validní záznamy <i class="bi bi-arrow-repeat ms-1"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($typeTotal, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($typeValidTotal, 0, ',', ' ') ?></span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= $editType ? 'Upravit typ novinek' : 'Přidání typu novinek' ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_type">
                    <input type="hidden" name="id" value="<?= (int)($editType['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="news_type_poradi">Pořadí</label>
                        <input type="number" class="form-control" name="poradi" id="news_type_poradi" value="<?= htmlspecialchars($typeOrderValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_type_nazev_cz">Název CZ</label>
                        <input type="text" class="form-control" name="nazev_cz" id="news_type_nazev_cz" required value="<?= htmlspecialchars((string)($editType['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_type_nazev_en">Název EN</label>
                        <input type="text" class="form-control" name="nazev_en" id="news_type_nazev_en" value="<?= htmlspecialchars((string)($editType['nazev_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_type_color">Color</label>
                        <input type="text" class="form-control" name="color" id="news_type_color" value="<?= htmlspecialchars((string)($editType['color'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="#ee4c50 nebo text">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_type_popis_cz">Popis CZ</label>
                        <textarea class="form-control" name="popis_cz" id="news_type_popis_cz" rows="3"><?= htmlspecialchars((string)($editType['popis_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_type_popis_en">Popis EN</label>
                        <textarea class="form-control" name="popis_en" id="news_type_popis_en" rows="3"><?= htmlspecialchars((string)($editType['popis_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <?= admin_auto_translate_checkbox($editType ?? null, 'news_type_auto_translate_en') ?>
                    </div>

                    <?php if ($editType): ?>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="valid" id="news_type_valid" value="1" <?= (int)($editType['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="news_type_valid">valid</label>
                        </div>

                        <div class="small text-muted mb-3">
                            Založeno: <?= htmlspecialchars((string)format_datetime_www((string)($editType['ts_i'] ?? '')), ENT_QUOTES, 'UTF-8') ?>;
                            Založil: <?= htmlspecialchars((string)($editType['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                            Upraveno: <?= htmlspecialchars((string)format_datetime_www((string)($editType['ts_u'] ?? '')), ENT_QUOTES, 'UTF-8') ?>;
                            Upravil: <?= htmlspecialchars((string)($editType['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Uložit typ</button>
                    <?php if ($editType): ?>
                        <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>">Zrušit editaci</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Typy novinek</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($typeLimit, 0, ',', ' ') ?> záznamů</span>
                <?php if ($typeCount > $typeLimit): ?>
                    <a href="<?= $baseUrl ?>&amp;limit=0&amp;valid=<?= (int)$valid ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ms-2">
                        načíst všechny záznamy (<?= number_format($typeCount, 0, ',', ' ') ?>) <i class="bi bi-arrow-repeat ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table
                            class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100"
                            data-order='[[ 1, "asc" ], [ 0, "asc" ]]'
                            data-page-length="500"
                    >
                        <thead class="table-dark align-middle">
                        <tr>
                            <th class="no-filter">ID</th>
                            <th class="text-filter dt-autocomplete">Pořadí</th>
                            <th class="text-filter dt-autocomplete">Název</th>
                            <th class="text-filter dt-autocomplete">Color</th>
                            <th class="no-filter">Valid</th>
                            <th class="text-filter dt-autocomplete">Upraveno</th>
                            <th class="no-sort no-filter">Upravit</th>
                            <th class="no-sort no-filter">Smazat</th>
                        </tr>
                        </thead>
                        <tfoot class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pořadí</th>
                            <th>Název</th>
                            <th>Color</th>
                            <th>Valid</th>
                            <th>Upraveno</th>
                            <th>Upravit</th>
                            <th>Smazat</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><?= (int)$type['id'] ?></td>
                                <td><?= (int)($type['poradi'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($type['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($type['color'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <?php if ((int)($type['valid'] ?? 0) === 1): ?>
                                        <span class="badge text-bg-success">ANO</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">NE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string)format_datetime_www((string)($type['ts_u'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    <br><small class="text-muted"><?= htmlspecialchars((string)($type['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-success btn-sm" href="<?= $baseUrl ?>&amp;edit_type=<?= (int)$type['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Upravit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-danger btn-sm" href="<?= $baseUrl ?>&amp;delete_type=<?= (int)$type['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Smazat" data-confirm="Opravdu smazat typ novinek?">
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
