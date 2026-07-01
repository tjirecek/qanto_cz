<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_galerie.php';

$secPage = sprintf('%02d', (int)($sec_page ?? ($_GET['sec_page'] ?? 1)));
$messages = [];
$errors = [];
$baseUrl = 'index.php?section=01&amp;page=03';
$defaultLimit = 500;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
if ($limit < 0) {
    $limit = $defaultLimit;
}
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = $valid === 0 ? 0 : 1;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save_type') {
            $id = galerie_int_or_null($_POST['id'] ?? null);
            galerie_type_save($_POST, $id);
            $messages[] = $id === null ? 'Typ galerie byl vytvořen.' : 'Typ galerie byl uložen.';
            $secPage = '04';
        } elseif ($action === 'save_gallery') {
            $id = galerie_int_or_null($_POST['id'] ?? null);
            $galleryId = galerie_save($_POST, $id);
            $messages[] = $id === null ? 'Galerie byla vytvořena.' : 'Galerie byla uložena.';
            $secPage = $id === null ? '05' : '03';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'upload_photos') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $result = galerie_upload_photos($galleryId, $_FILES['photos'] ?? []);
            if ($result['ok'] !== []) {
                $messages[] = 'Nahráno fotek: ' . count($result['ok']) . '.';
            }
            foreach ($result['error'] as $error) {
                $errors[] = $error;
            }
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'save_photo') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            galerie_photo_save_meta((int)($_POST['photo_id'] ?? 0), $_POST);
            $messages[] = 'Fotografie byla uložena.';
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'delete_photo') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            galerie_photo_delete((int)($_POST['photo_id'] ?? 0));
            $messages[] = 'Fotografie byla smazána.';
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'regenerate_thumbs') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $result = galerie_regenerate_thumbnails($galleryId);
            $messages[] = 'Vygenerováno náhledů: ' . (int)$result['ok'] . '.';
            foreach ($result['error'] as $error) {
                $errors[] = $error;
            }
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'sort_photos_by_name') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            galerie_photo_poradi_update($galleryId);
            $messages[] = 'Pořadí fotek bylo přepočítáno podle názvu souboru.';
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'save_photo_grid_order') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $orderRaw = trim((string)($_POST['photo_order'] ?? ''));
            $order = $orderRaw === '' ? [] : array_map('intval', explode(',', $orderRaw));
            $updated = galerie_photo_save_order($galleryId, $order);
            $messages[] = 'Pořadí fotek bylo uloženo. Upraveno záznamů: ' . $updated . '.';
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'remove_photo_duplicates') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $count = galerie_photo_duplicity_delete($galleryId);
            $messages[] = 'Odstraněno duplicit: ' . $count . '.';
            $secPage = '05';
            $_GET['id'] = (string)$galleryId;
        } elseif ($action === 'delete_invalid_photo_files') {
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $result = galerie_invalid_photo_files_delete($galleryId);
            $messages[] = 'Fyzické čištění nevalidních fotek: záznamů ' . (int)$result['photos']
                . ', smazáno souborů ' . (int)$result['files_deleted']
                . ', chybějících souborů ' . (int)$result['files_missing']
                . ', přeskočeno kvůli validnímu záznamu ' . (int)$result['files_skipped'] . '.';
            foreach ($result['errors'] as $error) {
                $errors[] = $error;
            }
            $secPage = '05';
            $valid = 0;
            $_GET['id'] = (string)$galleryId;
            $_GET['valid'] = '0';
        }
    }

    if (isset($_GET['delete_type'])) {
        galerie_type_delete((int)$_GET['delete_type']);
        $messages[] = 'Typ galerie byl smazán.';
        $secPage = '04';
    }

    if (isset($_GET['delete_gallery'])) {
        galerie_delete((int)$_GET['delete_gallery']);
        $messages[] = 'Galerie byla smazána.';
        $secPage = '01';
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$typeFilter = galerie_int_or_null($_GET['type_id'] ?? null);
$galleryId = galerie_int_or_null($_GET['id'] ?? null);
$editTypeId = galerie_int_or_null($_GET['edit_type'] ?? null);
$typeCount = galerie_types_count($valid);
$typeLimit = ($limit === 0 || $typeCount <= $limit) ? $typeCount : $limit;
$types = galerie_types_all(true, $typeLimit, $valid);

$toggleParams = [
    'section' => '01',
    'page' => '03',
    'sec_page' => $secPage,
];
if ($galleryId !== null) {
    $toggleParams['id'] = (string)$galleryId;
}
if ($typeFilter !== null) {
    $toggleParams['type_id'] = (string)$typeFilter;
}
if ($limit !== $defaultLimit) {
    $toggleParams['limit'] = (string)$limit;
}
$toggleParams['valid'] = $valid === 1 ? '0' : '1';
$validToggleUrl = 'index.php?' . http_build_query($toggleParams, '', '&amp;');

$validExportParams = [
    'type' => ($secPage === '05' && $galleryId !== null) ? 'photos_valid' : 'galleries_valid',
];
if ($validExportParams['type'] === 'photos_valid') {
    $validExportParams['gallery_id'] = (string)$galleryId;
} elseif ($typeFilter !== null) {
    $validExportParams['type_id'] = (string)$typeFilter;
}
$validExportUrl = BASE_URL . 'secure/functions/ajax/galerie_export.php?' . http_build_query($validExportParams);
$validExportLabel = $validExportParams['type'] === 'photos_valid'
    ? 'export validních fotek'
    : 'export validních galerií';

$galleryTotal = galerie_count(null, 0) + galerie_count(null, 1);
$galleryValidTotal = galerie_count(null, 1);
$currentGalleryTotal = $typeFilter !== null
    ? galerie_count($typeFilter, 0) + galerie_count($typeFilter, 1)
    : $galleryTotal;
$currentGalleryValid = $typeFilter !== null
    ? galerie_count($typeFilter, 1)
    : $galleryValidTotal;
$galleryFilterTypes = galerie_types_all(true, 0, 1);
$currentTypeName = 'vše';
if ($typeFilter !== null) {
    $currentType = galerie_type_get($typeFilter);
    $currentTypeName = (string)($currentType['nazev_cz'] ?? 'typ #' . $typeFilter);
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fotogalerie</h1>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($secPage === '01'): ?>
            <a href="<?= $baseUrl ?>&amp;sec_page=02"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                přidat galerii <i class="bi bi-plus-circle ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="<?= $baseUrl ?>&amp;sec_page=04"
           class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            typy galerií <i class="bi bi-list-ul ms-1"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $validToggleUrl ?>"
                   class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                    zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
                </a>
            <?php else: ?>
                <a href="<?= $validToggleUrl ?>"
                   class="d-none d-sm-inline-block btn btn-sm btn-outline-primary shadow-sm">
                    zobrazit validní záznamy <i class="bi bi-arrow-repeat ms-1"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ((int)admin_session_prava() === 1): ?>
            <a href="<?= galerie_e($validExportUrl) ?>"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"
               title="<?= galerie_e($validExportLabel) ?>">
                <?= galerie_e($validExportLabel) ?> <i class="bi bi-download ms-1"></i>
            </a>
        <?php endif; ?>

        <?php if ($secPage === '01'): ?>
            <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($galleryTotal, 0, ',', ' ') ?></span>
            <span class="btn btn-sm btn-light shadow-sm">aktivní: <?= number_format($galleryValidTotal, 0, ',', ' ') ?></span>
            <span class="btn btn-sm btn-primary shadow-sm"><?= galerie_e($currentTypeName) ?>: <?= number_format($currentGalleryTotal, 0, ',', ' ') ?></span>
            <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($currentGalleryValid, 0, ',', ' ') ?></span>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i><?= galerie_e($message) ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i><?= galerie_e($error) ?></div>
<?php endforeach; ?>

<?php if ($secPage === '04'): ?>
    <?php $editType = $editTypeId !== null ? galerie_type_get($editTypeId) : null; ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= $editType ? 'Upravit typ galerie' : 'Přidání typu galerie' ?></h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="save_type">
                        <input type="hidden" name="id" value="<?= (int)($editType['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label" for="type_poradi">Pořadí</label>
                            <input type="number" class="form-control" name="poradi" id="type_poradi" value="<?= galerie_e($editType['poradi'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <ul class="nav nav-tabs admin-lang-tabs" id="galerieTypeLangTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="galerie-type-cz-tab" data-bs-toggle="tab" data-bs-target="#galerie-type-cz-pane" type="button" role="tab" aria-controls="galerie-type-cz-pane" aria-selected="true">CZ</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="galerie-type-en-tab" data-bs-toggle="tab" data-bs-target="#galerie-type-en-pane" type="button" role="tab" aria-controls="galerie-type-en-pane" aria-selected="false">EN</button>
                                </li>
                            </ul>

                            <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="galerieTypeLangTabsContent">
                                <div class="tab-pane fade show active" id="galerie-type-cz-pane" role="tabpanel" aria-labelledby="galerie-type-cz-tab" tabindex="0">
                                    <div class="mb-3">
                                        <label class="form-label" for="type_nazev_cz">Název typu galerie CZ</label>
                                        <input type="text" class="form-control" name="nazev_cz" id="type_nazev_cz" required value="<?= galerie_e($editType['nazev_cz'] ?? '') ?>" data-translate-source="type_nazev" data-translate-format="text">
                                    </div>

                                    <div>
                                        <label class="form-label" for="type_popis_cz">Popis CZ</label>
                                        <textarea class="form-control" name="popis_cz" id="type_popis_cz" rows="3" data-translate-source="type_popis" data-translate-format="text"><?= galerie_e($editType['popis_cz'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="galerie-type-en-pane" role="tabpanel" aria-labelledby="galerie-type-en-tab" tabindex="0">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div class="text-muted small">EN pole lze předvyplnit překladem aktuálních CZ hodnot z tohoto formuláře.</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".galerie-type-translate-status">
                                                <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                            </button>
                                            <span class="small text-muted galerie-type-translate-status"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="type_nazev_en">Název typu galerie EN</label>
                                        <input type="text" class="form-control" name="nazev_en" id="type_nazev_en" value="<?= galerie_e($editType['nazev_en'] ?? '') ?>" data-translate-target="type_nazev">
                                    </div>

                                    <div>
                                        <label class="form-label" for="type_popis_en">Popis EN</label>
                                        <textarea class="form-control" name="popis_en" id="type_popis_en" rows="3" data-translate-target="type_popis"><?= galerie_e($editType['popis_en'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($editType): ?>
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="valid" id="type_valid" value="1" <?= (int)($editType['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="type_valid">valid</label>
                            </div>

                            <div class="small text-muted mb-3">
                                Založeno: <?= galerie_e(galerie_datetime_www($editType['ts_i'] ?? '')) ?>;
                                Založil: <?= galerie_e($editType['user_i'] ?? '') ?>;
                                Upraveno: <?= galerie_e(galerie_datetime_www($editType['ts_u'] ?? '')) ?>;
                                Upravil: <?= galerie_e($editType['user_u'] ?? '') ?>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Uložit typ</button>
                        <?php if ($editType): ?>
                            <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>&amp;sec_page=04">Zrušit editaci</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline">Typy galerií</h6>
                    <span class="d-none d-sm-inline-block ms-2">načteno <?= (int)$typeLimit ?> záznamů</span>
                    <?php if ($typeCount > $typeLimit): ?>
                        <a href="<?= $baseUrl ?>&amp;sec_page=04&amp;limit=0&amp;valid=<?= (int)$valid ?>"
                           class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ms-2">
                            načíst všechny záznamy (<?= (int)$typeCount ?>) <i class="bi bi-arrow-repeat ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table
                            class="table table-striped table-hover table-bordered table-sm js-datatable align-middle"
                            data-order='[[ 1, "asc" ], [ 0, "asc" ]]'
                            data-page-length="500"
                            width="100%"
                            cellspacing="0"
                    >
                        <thead class="table-dark align-middle">
                        <tr>
                            <th>ID</th>
                            <th>Pořadí</th>
                            <th class="text-filter dt-autocomplete">Název</th>
                            <th>Valid</th>
                            <th class="no-sort no-filter">Upravit</th>
                            <th class="no-sort no-filter">Smazat</th>
                        </tr>
                        </thead>
                        <tfoot class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pořadí</th>
                            <th>Název</th>
                            <th>Valid</th>
                            <th>Upravit</th>
                            <th>Smazat</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><?= (int)$type['id'] ?></td>
                                <td><?= (int)$type['poradi'] ?></td>
                                <td><?= galerie_e($type['nazev_cz'] ?? '') ?></td>
                                <td><?= (int)$type['valid'] === 1 ? 'ANO' : 'NE' ?></td>
                                <td class="text-center">
                                    <a class="btn btn-success btn-sm" href="<?= $baseUrl ?>&amp;sec_page=04&amp;edit_type=<?= (int)$type['id'] ?>" title="Upravit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-danger btn-sm" href="<?= $baseUrl ?>&amp;sec_page=04&amp;delete_type=<?= (int)$type['id'] ?>" title="Smazat" data-confirm="Opravdu smazat typ galerie?">
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
<?php elseif (in_array($secPage, ['02', '03'], true)): ?>
    <?php
    $gallery = $secPage === '03' && $galleryId !== null ? galerie_get($galleryId) : null;
    if ($secPage === '03' && $gallery === null):
    ?>
        <div class="alert alert-warning">Galerie nebyla nalezena.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= $gallery ? 'Editace galerie' : 'Přidání galerie' ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_gallery">
                    <input type="hidden" name="id" value="<?= (int)($gallery['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="galerie_datum">Datum</label>
                            <input type="date" class="form-control" name="datum" id="galerie_datum" value="<?= galerie_e(galerie_date_form($gallery['datum'] ?? null)) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="galerie_typ">Typ galerie</label>
                            <select class="form-select" name="galerie_typ" id="galerie_typ">
                                <?php galerie_typ_option_form((int)($gallery['galerie_typ'] ?? 0)); ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <ul class="nav nav-tabs admin-lang-tabs" id="galerieLangTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="galerie-cz-tab" data-bs-toggle="tab" data-bs-target="#galerie-cz-pane" type="button" role="tab" aria-controls="galerie-cz-pane" aria-selected="true">CZ</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="galerie-en-tab" data-bs-toggle="tab" data-bs-target="#galerie-en-pane" type="button" role="tab" aria-controls="galerie-en-pane" aria-selected="false">EN</button>
                                </li>
                            </ul>

                            <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="galerieLangTabsContent">
                                <div class="tab-pane fade show active" id="galerie-cz-pane" role="tabpanel" aria-labelledby="galerie-cz-tab" tabindex="0">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label" for="galerie_nazev_cz">Název galerie CZ</label>
                                            <input type="text" class="form-control" name="nazev_cz" id="galerie_nazev_cz" required value="<?= galerie_e($gallery['nazev_cz'] ?? '') ?>" data-translate-source="galerie_nazev" data-translate-format="text">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="galerie_popis_cz">Popis CZ</label>
                                            <textarea class="form-control js-tinymce" data-tinymce-height="260" name="popis_cz" id="galerie_popis_cz" data-translate-source="galerie_popis" data-translate-format="html"><?= galerie_e($gallery['popis_cz'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="galerie-en-pane" role="tabpanel" aria-labelledby="galerie-en-tab" tabindex="0">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div class="text-muted small">EN pole lze předvyplnit překladem aktuálních CZ hodnot z tohoto formuláře.</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".galerie-translate-status">
                                                <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                            </button>
                                            <span class="small text-muted galerie-translate-status"></span>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label" for="galerie_nazev_en">Název galerie EN</label>
                                            <input type="text" class="form-control" name="nazev_en" id="galerie_nazev_en" value="<?= galerie_e($gallery['nazev_en'] ?? '') ?>" data-translate-target="galerie_nazev">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="galerie_popis_en">Popis EN</label>
                                            <textarea class="form-control js-tinymce" data-tinymce-height="220" name="popis_en" id="galerie_popis_en" data-translate-target="galerie_popis"><?= galerie_e($gallery['popis_en'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($gallery): ?>
                            <div class="col-12 col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="valid" id="galerie_valid" value="1" <?= (int)($gallery['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="galerie_valid">valid</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="small text-muted">
                                    Založeno: <?= galerie_e(galerie_datetime_www($gallery['ts_i'] ?? '')) ?>;
                                    Založil: <?= galerie_e($gallery['user_i'] ?? '') ?>;
                                    Upraveno: <?= galerie_e(galerie_datetime_www($gallery['ts_u'] ?? '')) ?>;
                                    Upravil: <?= galerie_e($gallery['user_u'] ?? '') ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Uložit galerii</button>
                        <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>&amp;sec_page=01">Zpět na výpis</a>
                        <?php if ($gallery): ?>
                            <a class="btn btn-outline-success" href="<?= $baseUrl ?>&amp;sec_page=05&amp;id=<?= (int)$gallery['id'] ?>">Fotografie</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php elseif ($secPage === '05'): ?>
    <?php $gallery = $galleryId !== null ? galerie_get($galleryId) : null; ?>
    <?php if ($gallery === null): ?>
        <div class="alert alert-warning">Galerie nebyla nalezena.</div>
    <?php else: ?>
        <?php
        $photoCount = galerie_photos_count((int)$gallery['id'], $valid);
        $photoLimit = ($limit === 0 || $photoCount <= $limit) ? $photoCount : $limit;
        $photos = galerie_photos((int)$gallery['id'], $valid, $photoLimit);
        $canDragOrder = $photoCount === $photoLimit && $photos !== [];
        ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Fotografie: <?= galerie_e($gallery['nazev_cz'] ?? '') ?></h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= (int)$photoLimit ?> záznamů</span>
                <?php if ($photoCount > $photoLimit): ?>
                    <a href="<?= $baseUrl ?>&amp;sec_page=05&amp;id=<?= (int)$gallery['id'] ?>&amp;limit=0&amp;valid=<?= (int)$valid ?>"
                       class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ms-2">
                        načíst všechny záznamy (<?= (int)$photoCount ?>) <i class="bi bi-arrow-repeat ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_photos">
                    <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="photos">Nahrát fotografie</label>
                        <input type="file" class="form-control" name="photos[]" id="photos" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="form-text">
                            Povolené formáty: JPG, PNG, WebP. Hlavní fotka se zmenší na max <?= galerie_orig_width_limit() ?>x<?= galerie_orig_height_limit() ?> px, náhled na max <?= galerie_thumb_width_limit() ?>x<?= galerie_thumb_height_limit() ?> px.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm">nahrát vybrané fotky <i class="bi bi-upload ms-1"></i></button>
                </form>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <form method="post">
                <input type="hidden" name="action" value="regenerate_thumbs">
                <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                <button type="submit" class="btn btn-sm btn-primary shadow-sm">přegenerovat náhledy <i class="bi bi-arrow-repeat ms-1"></i></button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="sort_photos_by_name">
                <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                <button type="submit" class="btn btn-sm btn-primary shadow-sm">seřadit podle souboru <i class="bi bi-sort-alpha-down ms-1"></i></button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="remove_photo_duplicates">
                <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger shadow-sm">odstranit duplicity <i class="bi bi-slash-circle ms-1"></i></button>
            </form>
            <?php if ($valid === 0): ?>
                <form method="post" data-confirm="Opravdu fyzicky smazat soubory všech nevalidních fotek v této galerii? Databázové záznamy zůstanou nevalidní.">
                    <input type="hidden" name="action" value="delete_invalid_photo_files">
                    <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger shadow-sm">smazat soubory nevalidních fotek <i class="bi bi-trash ms-1"></i></button>
                </form>
            <?php endif; ?>
            <?php if ((int)admin_session_prava() === 1): ?>
                <a href="<?= galerie_e(BASE_URL . 'secure/functions/ajax/galerie_export.php?type=photos_valid&gallery_id=' . (int)$gallery['id']) ?>"
                   class="btn btn-sm btn-primary shadow-sm">
                    export validních fotek <i class="bi bi-download ms-1"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$canDragOrder && $photoCount > $photoLimit): ?>
            <div class="alert alert-info py-2">
                Pro ruční přetahování pořadí nejdřív načti všechny fotografie v galerii.
            </div>
        <?php endif; ?>

        <form method="post" class="mb-3" id="galeriePhotoOrderForm">
            <input type="hidden" name="action" value="save_photo_grid_order">
            <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
            <input type="hidden" name="photo_order" id="galeriePhotoOrderInput" value="">
            <button type="submit" class="btn btn-sm btn-success shadow-sm d-none" id="galeriePhotoOrderSubmit">
                uložit nové pořadí <i class="bi bi-check2-circle ms-1"></i>
            </button>
        </form>

        <div class="galerie-photo-grid" id="galeriePhotoGrid" data-sortable="<?= $canDragOrder ? '1' : '0' ?>">
            <?php foreach ($photos as $photo): ?>
                <?php
                $photoId = (int)$photo['id'];
                $photoFile = (string)$photo['soubor'];
                $photoTitle = (string)($photo['nazev_cz'] ?: $photoFile);
                ?>
                <button
                        type="button"
                        class="galerie-photo-thumb"
                        draggable="<?= $canDragOrder ? 'true' : 'false' ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#galeriePhotoEditModal"
                        data-photo-id="<?= $photoId ?>"
                        data-photo-file="<?= galerie_e($photoFile) ?>"
                        data-photo-thumb="<?= galerie_e(galerie_media_url((int)$gallery['id'], $photoFile, true)) ?>"
                        data-photo-original="<?= galerie_e(galerie_media_url((int)$gallery['id'], $photoFile)) ?>"
                        data-photo-order="<?= (int)$photo['poradi'] ?>"
                        data-photo-cz="<?= galerie_e($photo['nazev_cz'] ?? '') ?>"
                        data-photo-en="<?= galerie_e($photo['nazev_en'] ?? '') ?>"
                        data-photo-valid="<?= (int)($photo['valid'] ?? 1) ?>"
                        data-photo-ts-i="<?= galerie_e(galerie_datetime_www($photo['ts_i'] ?? '')) ?>"
                        data-photo-user-i="<?= galerie_e($photo['user_i'] ?? '') ?>"
                        data-photo-ts-u="<?= galerie_e(galerie_datetime_www($photo['ts_u'] ?? '')) ?>"
                        data-photo-user-u="<?= galerie_e($photo['user_u'] ?? '') ?>"
                        title="<?= galerie_e($photoTitle) ?>"
                >
                    <img src="<?= galerie_e(galerie_media_url((int)$gallery['id'], $photoFile, true)) ?>" alt="<?= galerie_e($photoTitle) ?>" loading="lazy">
                    <span class="galerie-photo-order"><?= (int)$photo['poradi'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if ($photos === []): ?>
            <div class="alert alert-info">Galerie zatím nemá žádné fotografie.</div>
        <?php endif; ?>

        <div class="modal fade" id="galeriePhotoEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editace fotografie</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-5">
                                <img src="" alt="" class="img-fluid rounded shadow-sm mb-2" id="galeriePhotoModalPreview">
                                <div class="small text-muted text-break" id="galeriePhotoModalFile"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="galeriePhotoModalOriginal" data-bs-toggle="modal" data-bs-target="#galeriePhotoLightboxModal">
                                    zobrazit originál <i class="bi bi-arrows-fullscreen ms-1"></i>
                                </button>
                            </div>

                            <div class="col-md-7">
                                <form method="post" id="galeriePhotoModalSaveForm">
                                    <input type="hidden" name="action" value="save_photo">
                                    <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                                    <input type="hidden" name="photo_id" id="galeriePhotoModalPhotoId" value="">

                                    <div class="mb-3">
                                        <label class="form-label" for="galeriePhotoModalOrder">Pořadí</label>
                                        <input type="number" class="form-control" name="poradi" id="galeriePhotoModalOrder" value="">
                                    </div>

                                    <div class="mb-3">
                                        <ul class="nav nav-tabs admin-lang-tabs" id="galeriePhotoLangTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="galerie-photo-cz-tab" data-bs-toggle="tab" data-bs-target="#galerie-photo-cz-pane" type="button" role="tab" aria-controls="galerie-photo-cz-pane" aria-selected="true">CZ</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="galerie-photo-en-tab" data-bs-toggle="tab" data-bs-target="#galerie-photo-en-pane" type="button" role="tab" aria-controls="galerie-photo-en-pane" aria-selected="false">EN</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="galeriePhotoLangTabsContent">
                                            <div class="tab-pane fade show active" id="galerie-photo-cz-pane" role="tabpanel" aria-labelledby="galerie-photo-cz-tab" tabindex="0">
                                                <label class="form-label" for="galeriePhotoModalCz">Název fotografie CZ</label>
                                                <input type="text" class="form-control" name="nazev_cz" id="galeriePhotoModalCz" value="" data-translate-source="photo_nazev" data-translate-format="text">
                                            </div>

                                            <div class="tab-pane fade" id="galerie-photo-en-pane" role="tabpanel" aria-labelledby="galerie-photo-en-tab" tabindex="0">
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                                    <div class="text-muted small">EN pole lze předvyplnit překladem aktuálního CZ názvu.</div>
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".galerie-photo-translate-status">
                                                            <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                                        </button>
                                                        <span class="small text-muted galerie-photo-translate-status"></span>
                                                    </div>
                                                </div>

                                                <label class="form-label" for="galeriePhotoModalEn">Název fotografie EN</label>
                                                <input type="text" class="form-control" name="nazev_en" id="galeriePhotoModalEn" value="" data-translate-target="photo_nazev">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="valid" id="galeriePhotoModalValid" value="1">
                                        <label class="form-check-label" for="galeriePhotoModalValid">valid</label>
                                    </div>

                                    <div class="small text-muted mb-3" id="galeriePhotoModalAudit"></div>

                                    <button type="submit" class="btn btn-success btn-sm">
                                        uložit <i class="bi bi-check2-circle ms-1"></i>
                                    </button>
                                </form>

                                <form method="post" class="mt-3" id="galeriePhotoModalDeleteForm" data-confirm="Opravdu smazat fotografii?">
                                    <input type="hidden" name="action" value="delete_photo">
                                    <input type="hidden" name="gallery_id" value="<?= (int)$gallery['id'] ?>">
                                    <input type="hidden" name="photo_id" id="galeriePhotoModalDeletePhotoId" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        smazat <i class="bi bi-trash ms-1"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="galeriePhotoLightboxModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white" id="galeriePhotoLightboxTitle">Originál fotografie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                    </div>
                    <div class="modal-body text-center pt-0">
                        <img src="" alt="" class="img-fluid rounded galerie-photo-lightbox-image" id="galeriePhotoLightboxImage">
                    </div>
                </div>
            </div>
        </div>


    <?php endif; ?>
<?php else: ?>
    <?php
    $galleryCount = galerie_count($typeFilter, $valid);
    $galleryLimit = ($limit === 0 || $galleryCount <= $limit) ? $galleryCount : $limit;
    $galleries = galerie_all($typeFilter, $valid, $galleryLimit);
    $galleryAllUrl = $baseUrl . '&amp;sec_page=01&amp;valid=' . (int)$valid;
    if ($limit !== $defaultLimit) {
        $galleryAllUrl .= '&amp;limit=' . (int)$limit;
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Výpis fotogalerií</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($galleryLimit, 0, ',', ' ') ?> záznamů</span>
                <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `galerie`</span>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $galleryAllUrl ?>"
                   class="btn btn-sm <?= $typeFilter === null ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Vše
                </a>

                <?php foreach ($galleryFilterTypes as $filterType): ?>
                    <?php
                    $filterTypeId = (int)($filterType['id'] ?? 0);
                    if ($filterTypeId <= 0) {
                        continue;
                    }
                    $filterUrl = $baseUrl . '&amp;sec_page=01&amp;valid=' . (int)$valid . '&amp;type_id=' . $filterTypeId;
                    if ($limit !== $defaultLimit) {
                        $filterUrl .= '&amp;limit=' . (int)$limit;
                    }
                    ?>
                    <a href="<?= $filterUrl ?>"
                       class="btn btn-sm <?= $typeFilter === $filterTypeId ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <?= galerie_e($filterType['nazev_cz'] ?? ('Typ #' . $filterTypeId)) ?>
                    </a>
                <?php endforeach; ?>

                <?php if ($galleryCount > $galleryLimit): ?>
                    <a href="<?= $baseUrl ?>&amp;sec_page=01&amp;limit=0&amp;valid=<?= (int)$valid ?><?= $typeFilter !== null ? '&amp;type_id=' . (int)$typeFilter : '' ?>"
                       class="btn btn-sm btn-outline-secondary">
                        načíst všechny záznamy (<?= number_format($galleryCount, 0, ',', ' ') ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100"
                    data-order='[[ 1, "desc" ], [ 0, "desc" ]]'
                    data-page-length="500"
                    id="DataTable"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="text-filter dt-autocomplete" data-type="date">Datum</th>
                    <th class="text-filter dt-autocomplete">Název</th>
                    <th class="text-filter dt-autocomplete">Typ</th>
                    <th class="no-filter">Fotky</th>
                    <th class="no-filter">Valid</th>
                    <th class="text-filter dt-autocomplete">Upraveno</th>
                    <th class="no-sort no-filter">Fotky</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Datum</th>
                    <th>Název</th>
                    <th>Typ</th>
                    <th>Fotky</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th>Fotky</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($galleries as $gallery): ?>
                    <tr>
                        <td><?= (int)$gallery['id'] ?></td>
                        <td><?= galerie_e(format_date_www((string)($gallery['datum'] ?? ''))) ?></td>
                        <td><?= galerie_e($gallery['nazev_cz'] ?? '') ?></td>
                        <td><?= galerie_e($gallery['typ_nazev'] ?? 'Bez typu') ?></td>
                        <td><?= (int)$gallery['photo_count'] ?></td>
                        <td class="text-center">
                            <?php if ((int)$gallery['valid'] === 1): ?>
                                <span class="badge text-bg-success">ANO</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">NE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= galerie_e(format_datetime_www((string)($gallery['ts_u'] ?? ''))) ?>
                            <br><small class="text-muted"><?= galerie_e($gallery['user_u'] ?? '') ?></small>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-primary btn-sm" href="<?= $baseUrl ?>&amp;sec_page=05&amp;id=<?= (int)$gallery['id'] ?>" title="Fotografie">
                                <i class="bi bi-images"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-success btn-sm" href="<?= $baseUrl ?>&amp;sec_page=03&amp;id=<?= (int)$gallery['id'] ?>" title="Upravit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-danger btn-sm" href="<?= $baseUrl ?>&amp;sec_page=01&amp;delete_gallery=<?= (int)$gallery['id'] ?>" title="Smazat" data-confirm="Opravdu smazat galerii?">
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
<?php endif; ?>
