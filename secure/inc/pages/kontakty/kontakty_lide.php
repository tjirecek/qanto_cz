<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_kontakty_lide.php';

global $pdo, $sec_page;

$activeSubpage = (string)($sec_page ?? '06') === '07' ? '07' : '06';
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = $valid === 0 ? 0 : 1;
$defaultLimit = 500;
$limit = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : $defaultLimit;
$messages = [];
$errors = [];
$editPersonId = max(0, (int)($_GET['edit_person'] ?? 0));
$editGroupId = max(0, (int)($_GET['edit_group'] ?? 0));
$showPersonForm = isset($_GET['add_person']) || $editPersonId > 0;
$showGroupForm = isset($_GET['add_group']) || $editGroupId > 0;
$personFormValues = kontakty_lide_default_person();
$groupFormValues = kontakty_lide_default_group();
$personAudit = [];
$groupAudit = [];

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_person') {
            $id = (int)($_POST['id'] ?? 0);
            $savedId = kontakty_lide_person_save($pdo, $_POST, $_FILES['userfile'] ?? null, $id > 0 ? $id : null);
            $messages[] = $id > 0 ? 'Osoba byla uložena.' : 'Osoba byla vytvořena.';
            $editPersonId = $savedId;
            $showPersonForm = true;
            $activeSubpage = '06';
        } elseif ($action === 'set_person_valid') {
            kontakty_lide_person_set_valid($pdo, (int)($_POST['id'] ?? 0), (int)($_POST['target_valid'] ?? 0));
            $messages[] = (int)($_POST['target_valid'] ?? 0) === 1 ? 'Osoba byla obnovena.' : 'Osoba byla znevalidněna.';
            $activeSubpage = '06';
        } elseif ($action === 'delete_person_image') {
            kontakty_lide_person_delete_image($pdo, (int)($_POST['id'] ?? 0));
            $messages[] = 'Fotka osoby byla smazána.';
            $activeSubpage = '06';
        } elseif ($action === 'save_group') {
            $id = (int)($_POST['id'] ?? 0);
            $savedId = kontakty_lide_group_save($pdo, $_POST, $id > 0 ? $id : null);
            $messages[] = $id > 0 ? 'Skupina byla uložena.' : 'Skupina byla vytvořena.';
            $editGroupId = $savedId;
            $showGroupForm = true;
            $activeSubpage = '07';
        } elseif ($action === 'set_group_valid') {
            kontakty_lide_group_set_valid($pdo, (int)($_POST['id'] ?? 0), (int)($_POST['target_valid'] ?? 0));
            $messages[] = (int)($_POST['target_valid'] ?? 0) === 1 ? 'Skupina byla obnovena.' : 'Skupina byla znevalidněna.';
            $activeSubpage = '07';
        }
    }

    if ($editPersonId > 0) {
        $person = kontakty_lide_person_get($pdo, $editPersonId);
        if ($person) {
            $personFormValues = array_merge($personFormValues, $person);
            $personAudit = $person;
            $showPersonForm = true;
        } else {
            $errors[] = 'Požadovaná osoba nebyla nalezena.';
            $editPersonId = 0;
            $showPersonForm = false;
        }
    }

    if ($editGroupId > 0) {
        $group = kontakty_lide_group_get($pdo, $editGroupId);
        if ($group) {
            $groupFormValues = array_merge($groupFormValues, $group);
            $groupAudit = $group;
            $showGroupForm = true;
        } else {
            $errors[] = 'Požadovaná skupina nebyla nalezena.';
            $editGroupId = 0;
            $showGroupForm = false;
        }
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    if (($_POST['action'] ?? '') === 'save_person') {
        $personFormValues = array_merge($personFormValues, $_POST);
        $showPersonForm = true;
        $activeSubpage = '06';
    } elseif (($_POST['action'] ?? '') === 'save_group') {
        $groupFormValues = array_merge($groupFormValues, $_POST);
        $showGroupForm = true;
        $activeSubpage = '07';
    }
}

$personCount = kontakty_lide_count($pdo, 'kontakty_lide', $valid);
$personLimit = ($limit === 0 || $personCount <= $limit) ? $personCount : $limit;
$people = $activeSubpage === '06' ? kontakty_lide_persons($pdo, $valid, $personLimit) : [];
$groupCount = kontakty_lide_count($pdo, 'kontakty_lide_skupiny', $valid);
$groupLimit = ($limit === 0 || $groupCount <= $limit) ? $groupCount : $limit;
$groups = $activeSubpage === '07' ? kontakty_lide_groups($pdo, $valid, $groupLimit) : [];
$allPeopleCount = kontakty_lide_count($pdo, 'kontakty_lide', null);
$allGroupCount = kontakty_lide_count($pdo, 'kontakty_lide_skupiny', null);
$validToggleUrl = kontakty_lide_page_url($activeSubpage, ['valid' => $valid === 1 ? 0 : 1, 'limit' => $limit]);
$showAllUrl = kontakty_lide_page_url($activeSubpage, ['valid' => $valid, 'limit' => 0]);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Kontakty: lidé ve firmě</h1>
        <div class="text-muted small">Shared agenda osob, skupin a kontaktních fotek.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if ($activeSubpage === '06'): ?>
            <a href="<?= kontakty_lide_page_url('06', ['add_person' => 1, 'valid' => $valid, 'limit' => $limit]) ?>" class="btn btn-sm btn-primary shadow-sm">
                nová osoba <i class="bi bi-plus-circle ms-1"></i>
            </a>
        <?php else: ?>
            <a href="<?= kontakty_lide_page_url('07', ['add_group' => 1, 'valid' => $valid, 'limit' => $limit]) ?>" class="btn btn-sm btn-primary shadow-sm">
                nová skupina <i class="bi bi-plus-circle ms-1"></i>
            </a>
        <?php endif; ?>

        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
            <?php else: ?>
                <a href="<?= $validToggleUrl ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
            <?php endif; ?>
        <?php endif; ?>

        <span class="btn btn-sm btn-light shadow-sm">osoby: <?= number_format($allPeopleCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">skupiny: <?= number_format($allGroupCount, 0, ',', ' ') ?></span>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '06' ? 'active' : '' ?>" href="index.php?section=01&amp;page=04&amp;sec_page=06">
            <i class="bi bi-people me-1"></i> Lidé
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '07' ? 'active' : '' ?>" href="index.php?section=01&amp;page=04&amp;sec_page=07">
            <i class="bi bi-diagram-3 me-1"></i> Skupiny
        </a>
    </li>
</ul>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i><?= kontakty_lide_e($message) ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i><?= kontakty_lide_e($error) ?></div>
<?php endforeach; ?>

<?php if ($activeSubpage === '06'): ?>
    <?php if ($showPersonForm): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold <?= $editPersonId > 0 ? 'text-success' : 'text-primary' ?> d-sm-inline"><?= $editPersonId > 0 ? 'Editace osoby' : 'Přidání osoby' ?></h6>
                <a href="<?= kontakty_lide_page_url('06', ['valid' => $valid, 'limit' => $limit]) ?>" class="btn btn-outline-secondary btn-sm">Zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="action" value="save_person">
                    <input type="hidden" name="id" value="<?= (int)$editPersonId ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-lg-2 col-md-3">
                            <label for="kontakty_lide_poradi" class="form-label">Pořadí</label>
                            <input type="number" name="poradi" id="kontakty_lide_poradi" class="form-control" value="<?= (int)($personFormValues['poradi'] ?? 0) ?>">
                        </div>
                        <div class="col-lg-4 col-md-9">
                            <label for="kontakty_lide_jmeno" class="form-label">Jméno a příjmení</label>
                            <input type="text" name="jmeno" id="kontakty_lide_jmeno" class="form-control" required value="<?= kontakty_lide_e($personFormValues['jmeno'] ?? '') ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="kontakty_lide_skupina_id" class="form-label">Skupina</label>
                            <select name="skupina_id" id="kontakty_lide_skupina_id" class="form-select">
                                <?= kontakty_lide_group_options($pdo, isset($personFormValues['skupina_id']) ? (int)$personFormValues['skupina_id'] : null) ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="kontakty_lide_userfile" class="form-label">Fotka</label>
                            <input type="file" name="userfile" id="kontakty_lide_userfile" class="form-control" accept="image/*">
                            <?php if (!empty($personFormValues['image'])): ?>
                                <div class="form-text">
                                    Aktuální soubor:
                                    <a href="<?= kontakty_lide_e(asset_version((string)$personFormValues['image'])) ?>" target="_blank" rel="noopener"><?= kontakty_lide_e(basename((string)$personFormValues['image'])) ?></a>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="delete_image" id="kontakty_lide_delete_image" value="1">
                                    <label class="form-check-label" for="kontakty_lide_delete_image">smazat aktuální fotku</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-3">
                            <label for="kontakty_lide_email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="kontakty_lide_email" class="form-control" value="<?= kontakty_lide_e($personFormValues['email'] ?? '') ?>">
                        </div>
                        <div class="col-lg-3">
                            <label for="kontakty_lide_mobil" class="form-label">Mobil / telefon</label>
                            <input type="text" name="mobil" id="kontakty_lide_mobil" class="form-control" value="<?= kontakty_lide_e($personFormValues['mobil'] ?? '') ?>">
                        </div>
                        <div class="col-lg-3">
                            <label for="kontakty_lide_web" class="form-label">Web</label>
                            <input type="text" name="web" id="kontakty_lide_web" class="form-control" value="<?= kontakty_lide_e($personFormValues['web'] ?? '') ?>">
                        </div>
                        <div class="col-lg-3">
                            <label for="kontakty_lide_funkce_cz" class="form-label">Funkce CZ</label>
                            <input type="text" name="funkce_cz" id="kontakty_lide_funkce_cz" class="form-control" value="<?= kontakty_lide_e($personFormValues['funkce_cz'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-3">
                            <label for="kontakty_lide_funkce_en" class="form-label">Funkce EN</label>
                            <input type="text" name="funkce_en" id="kontakty_lide_funkce_en" class="form-control" value="<?= kontakty_lide_e($personFormValues['funkce_en'] ?? '') ?>">
                        </div>
                        <div class="col-lg-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="visible" id="kontakty_lide_visible" value="1" <?= (int)($personFormValues['visible'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="kontakty_lide_visible">zobrazovat na webu</label>
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="valid" id="kontakty_lide_valid" value="1" <?= (int)($personFormValues['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="kontakty_lide_valid">valid</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label for="kontakty_lide_popis_cz" class="form-label">Popis CZ</label>
                            <textarea name="popis_cz" id="kontakty_lide_popis_cz" class="form-control js-tinymce" rows="8" data-tinymce-height="240"><?= (string)($personFormValues['popis_cz'] ?? '') ?></textarea>
                        </div>
                        <div class="col-lg-6">
                            <label for="kontakty_lide_popis_en" class="form-label">Popis EN</label>
                            <textarea name="popis_en" id="kontakty_lide_popis_en" class="form-control js-tinymce" rows="8" data-tinymce-height="240"><?= (string)($personFormValues['popis_en'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <?= admin_auto_translate_checkbox($personFormValues ?? null, 'kontakty_lide_person_auto_translate_en') ?>
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><?= $editPersonId > 0 ? 'Uložit osobu' : 'Vytvořit osobu' ?></button>
                        </div>
                        <?php if (!empty($personAudit)): ?>
                            <div class="col-12 small text-muted">
                                Založeno: <?= kontakty_lide_e(format_datetime_www((string)($personAudit['ts_i'] ?? ''))) ?>;
                                Založil: <?= kontakty_lide_e($personAudit['user_i'] ?? '') ?>;
                                Upraveno: <?= kontakty_lide_e(format_datetime_www((string)($personAudit['ts_u'] ?? ''))) ?>;
                                Upravil: <?= kontakty_lide_e($personAudit['user_u'] ?? '') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Lidé</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($personLimit, 0, ',', ' ') ?> záznamů</span>
                <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `kontakty_lide`</span>
            </div>
            <?php if ($personCount > $personLimit): ?>
                <a href="<?= $showAllUrl ?>" class="btn btn-sm btn-outline-secondary">načíst všechny záznamy (<?= number_format($personCount, 0, ',', ' ') ?>)</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-state-key="kontakty-lide-v1" data-column-filters="1" data-column-filter-placement="header" data-order='[[ 2, "asc" ], [ 0, "asc" ], [ 1, "asc" ]]' data-page-length="500">
                    <thead class="table-dark align-middle"><tr><th class="no-filter">Pořadí</th><th class="text-filter dt-autocomplete">Jméno</th><th class="text-filter dt-autocomplete">Skupina</th><th class="text-filter dt-autocomplete">Funkce</th><th class="text-filter dt-autocomplete">Telefon</th><th class="text-filter dt-autocomplete">E-mail</th><th class="select-filter">Foto</th><th class="select-filter">Zobrazovat</th><th class="select-filter">Valid</th><th class="no-filter">Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                    <tfoot class="table-light"><tr><th>Pořadí</th><th>Jméno</th><th>Skupina</th><th>Funkce</th><th>Telefon</th><th>E-mail</th><th>Foto</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                    <tbody>
                    <?php foreach ($people as $person): ?>
                        <?php $hasImage = trim((string)($person['image'] ?? '')) !== ''; ?>
                        <tr>
                            <td><?= (int)($person['poradi'] ?? 0) ?></td>
                            <td class="fw-semibold"><?= kontakty_lide_e($person['jmeno'] ?? '') ?></td>
                            <td><?= kontakty_lide_e($person['skupina_nazev'] ?? '') ?></td>
                            <td><?= kontakty_lide_e($person['funkce_cz'] ?? '') ?></td>
                            <td><?= kontakty_lide_e($person['mobil'] ?? '') ?></td>
                            <td><?php if ((string)($person['email'] ?? '') !== ''): ?><a href="mailto:<?= kontakty_lide_e($person['email']) ?>"><?= kontakty_lide_e($person['email']) ?></a><?php endif; ?></td>
                            <td class="text-center" data-search="<?= $hasImage ? 'ANO' : 'NE' ?>"><span class="badge text-bg-<?= $hasImage ? 'success' : 'secondary' ?>"><?= $hasImage ? 'ANO' : 'NE' ?></span></td>
                            <td class="text-center" data-search="<?= kontakty_lide_bool_label($person['visible'] ?? 0) ?>"><span class="badge text-bg-<?= (int)($person['visible'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= kontakty_lide_bool_label($person['visible'] ?? 0) ?></span></td>
                            <td class="text-center" data-search="<?= kontakty_lide_bool_label($person['valid'] ?? 0) ?>"><span class="badge text-bg-<?= (int)($person['valid'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= kontakty_lide_bool_label($person['valid'] ?? 0) ?></span></td>
                            <td><?= kontakty_lide_e(format_datetime_www((string)($person['ts_u'] ?? ''))) ?><br><small class="text-muted"><?= kontakty_lide_e($person['user_u'] ?? '') ?></small></td>
                            <td class="text-center text-nowrap">
                                <a class="btn btn-success btn-sm" title="Upravit" href="<?= kontakty_lide_page_url('06', ['edit_person' => (int)$person['id'], 'valid' => $valid, 'limit' => $limit]) ?>"><i class="bi bi-pencil-square"></i></a>
                                <?php if ($hasImage): ?>
                                    <form method="post" class="d-inline" data-confirm="Opravdu smazat fotku osoby?"><input type="hidden" name="action" value="delete_person_image"><input type="hidden" name="id" value="<?= (int)$person['id'] ?>"><button type="submit" class="btn btn-warning btn-sm" title="Smazat fotku"><i class="bi bi-image-alt"></i></button></form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" data-confirm="Opravdu změnit validitu osoby?"><input type="hidden" name="action" value="set_person_valid"><input type="hidden" name="id" value="<?= (int)$person['id'] ?>"><input type="hidden" name="target_valid" value="<?= (int)($person['valid'] ?? 0) === 1 ? 0 : 1 ?>"><button type="submit" class="btn btn-<?= (int)($person['valid'] ?? 0) === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)($person['valid'] ?? 0) === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)($person['valid'] ?? 0) === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if ($showGroupForm): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold <?= $editGroupId > 0 ? 'text-success' : 'text-primary' ?> d-sm-inline"><?= $editGroupId > 0 ? 'Editace skupiny' : 'Přidání skupiny' ?></h6>
                <a href="<?= kontakty_lide_page_url('07', ['valid' => $valid, 'limit' => $limit]) ?>" class="btn btn-outline-secondary btn-sm">Zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="save_group">
                    <input type="hidden" name="id" value="<?= (int)$editGroupId ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-lg-2 col-md-3"><label for="kontakty_lide_group_poradi" class="form-label">Pořadí</label><input type="number" class="form-control" name="poradi" id="kontakty_lide_group_poradi" value="<?= (int)($groupFormValues['poradi'] ?? 0) ?>"></div>
                        <div class="col-lg-4 col-md-9"><label for="kontakty_lide_group_nazev_cz" class="form-label">Název CZ</label><input type="text" class="form-control" name="nazev_cz" id="kontakty_lide_group_nazev_cz" required value="<?= kontakty_lide_e($groupFormValues['nazev_cz'] ?? '') ?>"></div>
                        <div class="col-lg-4 col-md-8"><label for="kontakty_lide_group_nazev_en" class="form-label">Název EN</label><input type="text" class="form-control" name="nazev_en" id="kontakty_lide_group_nazev_en" value="<?= kontakty_lide_e($groupFormValues['nazev_en'] ?? '') ?>"></div>
                        <div class="col-lg-1 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="visible" id="kontakty_lide_group_visible" value="1" <?= (int)($groupFormValues['visible'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="kontakty_lide_group_visible">web</label></div></div>
                        <div class="col-lg-1 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="valid" id="kontakty_lide_group_valid" value="1" <?= (int)($groupFormValues['valid'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="kontakty_lide_group_valid">valid</label></div></div>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><?= admin_auto_translate_checkbox($groupFormValues ?? null, 'kontakty_lide_group_auto_translate_en') ?></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-primary w-100"><?= $editGroupId > 0 ? 'Uložit skupinu' : 'Vytvořit skupinu' ?></button></div>
                        <?php if (!empty($groupAudit)): ?>
                            <div class="col-12 small text-muted">Upraveno: <?= kontakty_lide_e(format_datetime_www((string)($groupAudit['ts_u'] ?? ''))) ?>; Upravil: <?= kontakty_lide_e($groupAudit['user_u'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div><h6 class="m-0 fw-bold text-primary d-sm-inline">Skupiny osob</h6><span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($groupLimit, 0, ',', ' ') ?> záznamů</span><span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `kontakty_lide_skupiny`</span></div>
            <?php if ($groupCount > $groupLimit): ?><a href="<?= $showAllUrl ?>" class="btn btn-sm btn-outline-secondary">načíst všechny záznamy (<?= number_format($groupCount, 0, ',', ' ') ?>)</a><?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-state-key="kontakty-lide-skupiny-v1" data-column-filters="1" data-column-filter-placement="header" data-order='[[ 1, "asc" ], [ 2, "asc" ]]' data-page-length="500">
                    <thead class="table-dark align-middle"><tr><th class="no-filter">ID</th><th class="no-filter">Pořadí</th><th class="text-filter dt-autocomplete">Název</th><th class="text-filter dt-autocomplete">Název EN</th><th class="select-filter">Osob</th><th class="select-filter">Zobrazovat</th><th class="select-filter">Valid</th><th class="no-filter">Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                    <tfoot class="table-light"><tr><th>ID</th><th>Pořadí</th><th>Název</th><th>Název EN</th><th>Osob</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                    <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?= (int)$group['id'] ?></td>
                            <td><?= (int)($group['poradi'] ?? 0) ?></td>
                            <td class="fw-semibold"><?= kontakty_lide_e($group['nazev_cz'] ?? '') ?></td>
                            <td><?= kontakty_lide_e($group['nazev_en'] ?? '') ?></td>
                            <td class="text-end"><?= (int)($group['people_count'] ?? 0) ?></td>
                            <td class="text-center" data-search="<?= kontakty_lide_bool_label($group['visible'] ?? 0) ?>"><span class="badge text-bg-<?= (int)($group['visible'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= kontakty_lide_bool_label($group['visible'] ?? 0) ?></span></td>
                            <td class="text-center" data-search="<?= kontakty_lide_bool_label($group['valid'] ?? 0) ?>"><span class="badge text-bg-<?= (int)($group['valid'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= kontakty_lide_bool_label($group['valid'] ?? 0) ?></span></td>
                            <td><?= kontakty_lide_e(format_datetime_www((string)($group['ts_u'] ?? ''))) ?><br><small class="text-muted"><?= kontakty_lide_e($group['user_u'] ?? '') ?></small></td>
                            <td class="text-center text-nowrap">
                                <a class="btn btn-success btn-sm" title="Upravit" href="<?= kontakty_lide_page_url('07', ['edit_group' => (int)$group['id'], 'valid' => $valid, 'limit' => $limit]) ?>"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" class="d-inline" data-confirm="Opravdu změnit validitu skupiny?"><input type="hidden" name="action" value="set_group_valid"><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><input type="hidden" name="target_valid" value="<?= (int)($group['valid'] ?? 0) === 1 ? 0 : 1 ?>"><button type="submit" class="btn btn-<?= (int)($group['valid'] ?? 0) === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)($group['valid'] ?? 0) === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)($group['valid'] ?? 0) === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
