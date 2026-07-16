<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_obchodni_zastupci.php';

$defaultLimit = 100;
$limit = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : $defaultLimit;
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = in_array($valid, [0, 1], true) ? $valid : 1;
$show = isset($_GET['show']) ? (int)$_GET['show'] : 0;
$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$del = isset($_GET['del']) ? (int)$_GET['del'] : 0;
$alerts = [];
$pobockyOptions = [];
$formValues = obchodni_zastupci_default_form_data();
$formAudit = [];
$activeCount = 0;
$inactiveCount = 0;
$currentCount = 0;

try {
    $pobockyOptions = obchodni_zastupci_fetch_pobocky($pdo);

    if ($del > 0) {
        obchodni_zastupci_delete($pdo, $del);
        obchodni_zastupci_redirect([
            'limit' => $limit,
            'valid' => $valid,
            'msg' => 'deleted',
        ]);
    }

    if ($show === 1 && ($_POST['add'] ?? '') === '1') {
        try {
            $formValues = obchodni_zastupci_normalize_form_data($_POST);
            $formValues['image'] = obchodni_zastupci_image_upload($_FILES['userfile'] ?? null, '');
            $newId = obchodni_zastupci_add($pdo, $formValues);
            obchodni_zastupci_redirect([
                'show' => 2,
                'edit' => $newId,
                'limit' => $limit,
                'valid' => $valid,
                'msg' => 'created',
            ]);
        } catch (Throwable $e) {
            $alerts[] = [
                'type' => 'danger',
                'text' => $e->getMessage(),
            ];
        }
    }

    if ($show === 2 && $edit > 0) {
        $record = obchodni_zastupci_fetch_one($pdo, $edit);
        if (!$record) {
            $alerts[] = [
                'type' => 'warning',
                'text' => 'Zaznam obchodniho zastupce nebyl nalezen.',
            ];
            $show = 0;
        } else {
            $formValues = array_merge($formValues, $record);
            $formAudit = [
                'ts_i' => $record['ts_i'] ?? '',
                'ts_u' => $record['ts_u'] ?? '',
                'user_i' => $record['user_i'] ?? '',
                'user_u' => $record['user_u'] ?? '',
            ];

            if (($_POST['add'] ?? '') === '2') {
                try {
                    $existingImage = (string)($record['image'] ?? '');
                    $formValues = obchodni_zastupci_normalize_form_data($_POST);
                    if (!empty($_POST['delete_image'])) {
                        obchodni_zastupci_delete_image_file_if_unused($pdo, $existingImage, $edit);
                        $formValues['image'] = '';
                    } else {
                        $formValues['image'] = obchodni_zastupci_image_upload($_FILES['userfile'] ?? null, $existingImage);
                        if ($formValues['image'] !== $existingImage) {
                            obchodni_zastupci_delete_image_file_if_unused($pdo, $existingImage, $edit);
                        }
                    }
                    obchodni_zastupci_edit($pdo, $edit, $formValues);
                    obchodni_zastupci_redirect([
                        'show' => 2,
                        'edit' => $edit,
                        'limit' => $limit,
                        'valid' => $valid,
                        'msg' => 'updated',
                    ]);
                } catch (Throwable $e) {
                    $alerts[] = [
                        'type' => 'danger',
                        'text' => $e->getMessage(),
                    ];
                }
            }
        }
    }

    if (($_GET['msg'] ?? '') === 'created') {
        $alerts[] = ['type' => 'success', 'text' => 'Obchodni zastupce byl vytvoren.'];
    } elseif (($_GET['msg'] ?? '') === 'updated') {
        $alerts[] = ['type' => 'success', 'text' => 'Obchodni zastupce byl upraven.'];
    } elseif (($_GET['msg'] ?? '') === 'deleted') {
        $alerts[] = ['type' => 'success', 'text' => 'Obchodni zastupce byl deaktivovan.'];
    }

    $activeCount = obchodni_zastupci_count($pdo, 1);
    $inactiveCount = obchodni_zastupci_count($pdo, 0);
    $currentCount = obchodni_zastupci_count($pdo, $valid);
} catch (Throwable $e) {
    $alerts[] = [
        'type' => 'danger',
        'text' => 'Nepodarilo se nacist obchodni zastupce: ' . $e->getMessage(),
    ];
}

if ($limit === 0 || $currentCount <= $limit) {
    $limit = $currentCount;
}

if ($limit < 0) {
    $limit = $defaultLimit;
}

$totalCount = $activeCount + $inactiveCount;
$loadedCount = ($limit === 0 && $currentCount > 0) ? $currentCount : max(0, $limit);
$showInactiveUrl = obchodni_zastupci_page_url(['limit' => 9999, 'valid' => 0]);
$showActiveUrl = obchodni_zastupci_page_url(['limit' => $defaultLimit, 'valid' => 1]);
$showAllUrl = obchodni_zastupci_page_url(['limit' => 0, 'valid' => $valid]);
$addUrl = obchodni_zastupci_page_url(['show' => 1, 'limit' => $loadedCount, 'valid' => $valid]);
$cancelUrl = obchodni_zastupci_page_url(['limit' => $loadedCount, 'valid' => $valid]);
$hasOpenForm = in_array($show, [1, 2], true);
$tableClasses = 'table table-striped table-hover table-bordered table-sm align-middle w-100 js-datatable';
$tableWrapClasses = 'table-responsive';
$stateKey = $hasOpenForm ? 'obchodni-zastupci-detail-v1' : 'obchodni-zastupci-compact-v1';
$filterPlacement = 'header';
$selectedPobockaLabel = '';
foreach ($pobockyOptions as $pobockaOption) {
    if ((int)($formValues['pobocka_id'] ?? 0) === (int)($pobockaOption['id'] ?? 0)) {
        $selectedPobockaLabel = obchodni_zastupci_pobocka_label($pobockaOption);
        break;
    }
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Kontakty: Obchodní zástupci</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars($addUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat zástupce <i class="bi bi-plus-circle ms-1"></i>
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

        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($totalCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">aktivní: <?= number_format($activeCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-primary shadow-sm">OZ: <?= number_format($currentCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">načteno: <?= number_format($loadedCount, 0, ',', ' ') ?></span>
    </div>
</div>

<?php foreach ($alerts as $alert): ?>
    <div class="alert alert-<?= htmlspecialchars((string)$alert['type'], ENT_QUOTES) ?> mb-3" role="alert">
        <?= htmlspecialchars((string)$alert['text'], ENT_QUOTES) ?>
    </div>
<?php endforeach; ?>

<?php if ($show === 1 || ($show === 2 && $edit > 0)): ?>
    <?php
    $formActionValue = ($show === 1) ? 1 : 2;
    $formSubmitLabel = ($show === 1) ? 'Vytvořit zástupce' : 'Uložit změny';
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="m-0 fw-bold <?= $show === 1 ? 'text-primary' : 'text-success' ?> d-sm-inline">
                <?= $show === 1 ? 'Přidání obchodního zástupce' : 'Editace obchodního zástupce' ?>
            </h6>
            <a href="<?= htmlspecialchars($cancelUrl, ENT_QUOTES) ?>" class="btn btn-outline-secondary btn-sm">Zpět na výpis</a>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="row g-3 mb-3">
                    <div class="col-lg-5">
                        <label for="pobocka_display" class="form-label">Pobočka</label>
                        <input
                            type="hidden"
                            name="pobocka_id"
                            id="pobocka_id"
                            value="<?= (int)($formValues['pobocka_id'] ?? 0) ?>"
                            data-oz-pobocka-id
                        >
                        <div class="input-group">
                            <input
                                type="text"
                                id="pobocka_display"
                                class="form-control"
                                value="<?= htmlspecialchars($selectedPobockaLabel, ENT_QUOTES) ?>"
                                placeholder="Vyberte pobočku"
                                readonly
                                data-oz-pobocka-display
                            >
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#ozPobockaModal"
                            >
                                vybrat
                            </button>
                        </div>
                        <div class="form-text">Pobočka je povinná; vyberte ji ze seznamu v modalu.</div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="oblast_id" class="form-label">Oblast ID</label>
                        <input
                            type="number"
                            name="oblast_id"
                            id="oblast_id"
                            class="form-control"
                            value="<?= ($formValues['oblast_id'] === null || $formValues['oblast_id'] === '') ? '' : (int)$formValues['oblast_id'] ?>"
                        >
                        <div class="form-text">Oblastní agenda bude doplněna později.</div>
                    </div>

                    <div class="col-lg-1 col-md-3">
                        <label for="poradi" class="form-label">Pořadí</label>
                        <input type="number" name="poradi" id="poradi" class="form-control" value="<?= (int)($formValues['poradi'] ?? 0) ?>">
                    </div>

                    <div class="col-lg-4">
                        <label for="jmeno" class="form-label">Jméno</label>
                        <input
                            type="text"
                            name="jmeno"
                            id="jmeno"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($formValues['jmeno'] ?? ''), ENT_QUOTES) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-3">
                        <label for="mobil" class="form-label">Telefon / mobil</label>
                        <input type="text" name="mobil" id="mobil" class="form-control" value="<?= htmlspecialchars((string)($formValues['mobil'] ?? ''), ENT_QUOTES) ?>">
                    </div>

                    <div class="col-lg-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars((string)($formValues['email'] ?? ''), ENT_QUOTES) ?>">
                    </div>

                    <div class="col-lg-3">
                        <label for="web" class="form-label">Web</label>
                        <input type="text" name="web" id="web" class="form-control" value="<?= htmlspecialchars((string)($formValues['web'] ?? ''), ENT_QUOTES) ?>">
                    </div>

                    <div class="col-lg-3">
                        <label for="userfile" class="form-label">Fotka</label>
                        <input type="file" name="userfile" id="userfile" class="form-control" accept="image/*">
                        <?php if (!empty($formValues['image'])): ?>
                            <div class="form-text">
                                Aktuální soubor:
                                <a href="<?= htmlspecialchars(asset_version((string)$formValues['image']), ENT_QUOTES) ?>" target="_blank">
                                    <?= htmlspecialchars((string)basename((string)$formValues['image']), ENT_QUOTES) ?>
                                </a>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image" value="1">
                                <label class="form-check-label" for="delete_image">smazat aktuální fotku</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".oz-translate-status">
                                <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                            </button>
                            <span class="small text-muted oz-translate-status"></span>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <label for="popis_cz" class="form-label">Popis CZ</label>
                        <textarea name="popis_cz" id="popis_cz" class="form-control js-tinymce" rows="8" data-tinymce-height="280" data-translate-source="popis" data-translate-format="html"><?= (string)($formValues['popis_cz'] ?? '') ?></textarea>
                    </div>

                    <div class="col-lg-6">
                        <label for="popis_en" class="form-label">Popis EN</label>
                        <textarea name="popis_en" id="popis_en" class="form-control js-tinymce" rows="8" data-tinymce-height="280" data-translate-target="popis"><?= (string)($formValues['popis_en'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <?= admin_auto_translate_checkbox($formValues ?? null, 'oz_auto_translate_en') ?>
                    </div>

                    <div class="col-md-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ((int)($formValues['valid'] ?? 0) === 1 ? 'checked' : '') ?>>
                            <label class="form-check-label" for="valid">valid</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <input type="hidden" name="add" value="<?= (int)$formActionValue ?>">
                        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars($formSubmitLabel, ENT_QUOTES) ?></button>
                    </div>

                    <?php if (!empty($formAudit)): ?>
                        <div class="col-12 small text-muted">
                            Založeno: <?= isset($formAudit['ts_i']) ? htmlspecialchars((string)format_datetime_www((string)$formAudit['ts_i']), ENT_QUOTES) : '' ?>;
                            Založil: <?= htmlspecialchars((string)($formAudit['user_i'] ?? ''), ENT_QUOTES) ?>;
                            Upraveno: <?= isset($formAudit['ts_u']) ? htmlspecialchars((string)format_datetime_www((string)$formAudit['ts_u']), ENT_QUOTES) : '' ?>;
                            Upravil: <?= htmlspecialchars((string)($formAudit['user_u'] ?? ''), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <div class="modal fade" id="ozPobockaModal" tabindex="-1" aria-labelledby="ozPobockaModalLabel" aria-hidden="true" data-oz-pobocka-modal>
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="ozPobockaModalLabel">Vybrat pobočku</h5>
                                <div class="small text-muted">Kliknutím na řádek vyberete pobočku pro obchodního zástupce.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="ozPobockaSearch" class="form-label">Hledat pobočku</label>
                                <input
                                    type="search"
                                    id="ozPobockaSearch"
                                    class="form-control"
                                    placeholder="Název, typ nebo adresa"
                                    autocomplete="off"
                                    data-oz-pobocka-search
                                >
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Typ</th>
                                        <th>Název</th>
                                        <th>Adresa</th>
                                        <th class="text-end">Akce</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pobockyOptions as $pobocka): ?>
                                        <?php
                                        $pobockaLabel = obchodni_zastupci_pobocka_label($pobocka);
                                        $pobockaType = (string)($pobocka['typ'] ?? '');
                                        $pobockaName = (string)($pobocka['nazev_cz'] ?? '');
                                        $pobockaAddress = (string)($pobocka['adresa'] ?? '');
                                        ?>
                                        <tr data-oz-pobocka-row>
                                            <td><?= htmlspecialchars($pobockaType, ENT_QUOTES) ?></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($pobockaName, ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($pobockaAddress, ENT_QUOTES) ?></td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    data-oz-pobocka-choice
                                                    data-pobocka-id="<?= (int)$pobocka['id'] ?>"
                                                    data-pobocka-label="<?= htmlspecialchars($pobockaLabel, ENT_QUOTES) ?>"
                                                >
                                                    vybrat
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-warning mb-0 d-none" data-oz-pobocka-empty>
                                Nenalezena žádná pobočka pro zadaný filtr.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Obchodní zástupci</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($loadedCount, 0, ',', ' ') ?> záznamů</span>
            <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `obchodni_zastupci`</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?php if ($currentCount > $loadedCount): ?>
                <a href="<?= htmlspecialchars($showAllUrl, ENT_QUOTES) ?>" class="btn btn-sm btn-outline-secondary">
                    načíst všechny záznamy (<?= number_format($currentCount, 0, ',', ' ') ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <div class="<?= htmlspecialchars($tableWrapClasses, ENT_QUOTES) ?>">
            <table
                id="DataTableObchodniZastupci"
                class="<?= htmlspecialchars($tableClasses, ENT_QUOTES) ?>"
                data-state-key="<?= htmlspecialchars($stateKey, ENT_QUOTES) ?>"
                data-column-filters="1"
                data-column-filter-placement="<?= htmlspecialchars($filterPlacement, ENT_QUOTES) ?>"
                data-order='[[ 0, "asc" ], [ 1, "asc" ]]'
                data-page-length='100'
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">Pořadí</th>
                    <th class="text-filter dt-autocomplete">Jméno</th>
                    <th class="text-filter dt-autocomplete">Pobočka</th>
                    <th class="text-filter dt-autocomplete">Oblast</th>
                    <th class="text-filter dt-autocomplete">Telefon</th>
                    <th class="text-filter dt-autocomplete">E-mail</th>
                    <th class="no-filter">Valid</th>
                    <th class="no-filter">Upraveno</th>
                    <th class="no-sort no-filter">Akce</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>Pořadí</th>
                    <th>Jméno</th>
                    <th>Pobočka</th>
                    <th>Oblast</th>
                    <th>Telefon</th>
                    <th>E-mail</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th>Akce</th>
                </tr>
                </tfoot>

                <tbody>
                <?php obchodni_zastupci_vypis($pdo, $loadedCount, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
