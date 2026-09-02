<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_akce.php';

global $pdo, $sec_page;

$tab = (string)($sec_page ?? '01');
if (!in_array($tab, ['01', '02', '03', '04', '05'], true)) {
    $tab = '01';
}

$error = '';
$notice = '';
$csrfToken = (string)admin_session_get('rep_akce_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_akce_csrf_token', $csrfToken);
}

$valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
$visibleRaw = (string)($_GET['visible_filter'] ?? 'all');
$visibleFilter = in_array($visibleRaw, ['0', '1'], true) ? (int)$visibleRaw : null;
$typeIds = rep_akce_parse_ids($_GET['types'] ?? []);
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

$types = [];
$typeRows = [];
$offers = [];
$years = [];
$editOffer = null;
$editType = null;
$viewOffer = null;
$viewPages = [];
$editPages = [];
$editPageCount = 0;
$pageOutputFormat = rep_akce_page_output_format();
$pageImageQuality = rep_akce_page_image_quality();
$pageTargetKb = rep_akce_page_target_kb();

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění upravovat akční nabídky.');
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_offer') {
            $editId = rep_akce_save_offer($pdo, $_POST, $_FILES);
            $notice = 'Akční nabídka byla uložena.';
            $tab = '03';
        } elseif ($action === 'save_type') {
            $editId = rep_akce_save_type($pdo, $_POST);
            $notice = 'Typ akční nabídky byl uložen.';
            $tab = '04';
        } elseif ($action === 'invalidate_offer' || $action === 'validate_offer') {
            rep_akce_set_valid($pdo, 'rep_akce', (int)($_POST['id'] ?? 0), $action === 'validate_offer' ? 1 : 0);
            $notice = $action === 'validate_offer' ? 'Akční nabídka byla obnovena.' : 'Akční nabídka byla znevalidněna.';
            $tab = '01';
        } elseif ($action === 'invalidate_type' || $action === 'validate_type') {
            rep_akce_set_valid($pdo, 'rep_akce_typ', (int)($_POST['id'] ?? 0), $action === 'validate_type' ? 1 : 0);
            $notice = $action === 'validate_type' ? 'Typ byl obnoven.' : 'Typ byl znevalidněn.';
            $tab = '02';
        } elseif ($action === 'remove_pdf') {
            rep_akce_remove_file($pdo, (int)($_POST['id'] ?? 0), 'pdf_file');
            $notice = 'PDF bylo smazáno.';
            $editId = (int)($_POST['id'] ?? 0);
            $tab = '03';
        } elseif ($action === 'remove_cover') {
            rep_akce_remove_file($pdo, (int)($_POST['id'] ?? 0), 'cover_image');
            $notice = 'Obálka byla smazána.';
            $editId = (int)($_POST['id'] ?? 0);
            $tab = '03';
        } elseif ($action === 'remove_pages') {
            $removedPages = rep_akce_remove_pages($pdo, (int)($_POST['id'] ?? 0));
            $notice = 'Stránky prohlížeče byly smazány (' . $removedPages . ').';
            $editId = (int)($_POST['id'] ?? 0);
            $tab = '03';
        } elseif ($action === 'import_flip_pages') {
            $result = rep_akce_import_flip_pages($pdo, (int)($_POST['id'] ?? 0), true);
            $notice = 'Stránky z _flip/files/mobile byly převzaty (' . (int)$result['imported'] . ').';
            $editId = (int)($_POST['id'] ?? 0);
            $tab = '03';
        }

        $valid = isset($_POST['list_valid']) && (string)$_POST['list_valid'] === '0' ? 0 : 1;
        $visibleRaw = (string)($_POST['list_visible_filter'] ?? 'all');
        $visibleFilter = in_array($visibleRaw, ['0', '1'], true) ? (int)$visibleRaw : null;
        $typeIds = rep_akce_parse_ids($_POST['types'] ?? []);
        $year = isset($_POST['list_year']) && (int)$_POST['list_year'] > 0 ? (int)$_POST['list_year'] : null;
    }

    $types = rep_akce_types($pdo, 1);
    $years = rep_akce_years($pdo);
    if ($tab === '01') {
        $offers = rep_akce_offers($pdo, $typeIds, $year, $valid, $visibleFilter);
    } elseif ($tab === '02') {
        $typeRows = rep_akce_types($pdo, $valid);
    } elseif ($tab === '03') {
        $editOffer = $editId > 0 ? rep_akce_offer($pdo, $editId) : null;
        if ($editId > 0 && !$editOffer) {
            throw new RuntimeException('Akční nabídka nebyla nalezena.');
        }
        if ($editOffer) {
            $editPageCount = rep_akce_page_count($pdo, (int)$editOffer['id']);
            $editPages = rep_akce_pages($pdo, (int)$editOffer['id'], 12);
        }
    } elseif ($tab === '04') {
        $editType = $editId > 0 ? rep_akce_type($pdo, $editId) : null;
        if ($editId > 0 && !$editType) {
            throw new RuntimeException('Typ akční nabídky nebyl nalezen.');
        }
    } elseif ($tab === '05') {
        $viewOffer = rep_akce_offer($pdo, $viewId);
        if (!$viewOffer) {
            throw new RuntimeException('Akční nabídka nebyla nalezena.');
        }
        $viewPages = rep_akce_pages($pdo, $viewId);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$typeMap = array_fill_keys($typeIds, true);
$selectedTypeLabels = [];
foreach ($types as $type) {
    if (isset($typeMap[(int)$type['id']])) {
        $selectedTypeLabels[] = (string)$type['nazev_cz'];
    }
}
$validToggleParams = ['section' => '02', 'page' => '02', 'sec_page' => in_array($tab, ['03', '04', '05'], true) ? '01' : $tab, 'valid' => $valid === 1 ? '0' : '1'];
foreach ($typeIds as $typeId) {
    $validToggleParams['types'][] = (string)$typeId;
}
if ($visibleFilter !== null) {
    $validToggleParams['visible_filter'] = (string)$visibleFilter;
}
if ($year !== null && $year > 0) {
    $validToggleParams['year'] = (string)$year;
}
$validToggleUrl = 'index.php?' . http_build_query($validToggleParams, '', '&');
$validOfferCount = ($pdo instanceof PDO) ? rep_akce_offer_count($pdo, 1) : 0;
$invalidOfferCount = ($pdo instanceof PDO) ? rep_akce_offer_count($pdo, 0) : 0;
$validTypeCount = ($pdo instanceof PDO) ? rep_akce_type_count($pdo, 1) : 0;
$invalidTypeCount = ($pdo instanceof PDO) ? rep_akce_type_count($pdo, 0) : 0;

$formOffer = $editOffer ?? rep_akce_default_offer();
$formType = $editType ?? ['id' => 0, 'code' => '', 'poradi' => 0, 'nazev_cz' => '', 'nazev_en' => '', 'color' => '', 'newsletter_group' => '', 'valid' => 1];
$offersActive = in_array($tab, ['01', '03', '05'], true);
$typesActive = in_array($tab, ['02', '04'], true);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Akční nabídky</h1>
        <div class="text-muted small">Project agenda qanto.cz: PDF nabídky, historické obrázky a typy akcí.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if (in_array($tab, ['01', '02'], true)): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= rep_akce_e($validToggleUrl) ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
            <?php else: ?>
                <a href="<?= rep_akce_e($validToggleUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
            <?php endif; ?>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">akce: <?= number_format($validOfferCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">typy: <?= number_format($validTypeCount, 0, ',', ' ') ?></span>
        <?php if ($tab === '01'): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=03" class="btn btn-sm btn-primary shadow-sm">přidat nabídku <i class="bi bi-plus-circle ms-1"></i></a>
        <?php elseif ($tab === '02'): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=04" class="btn btn-sm btn-primary shadow-sm">přidat typ <i class="bi bi-plus-circle ms-1"></i></a>
        <?php endif; ?>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item"><a class="nav-link <?= $offersActive ? 'active' : '' ?>" href="index.php?section=02&amp;page=02&amp;sec_page=01"><i class="bi bi-tags me-1"></i> Akce</a></li>
    <li class="nav-item"><a class="nav-link <?= $typesActive ? 'active' : '' ?>" href="index.php?section=02&amp;page=02&amp;sec_page=02"><i class="bi bi-diagram-3 me-1"></i> Typy</a></li>
    <li class="nav-item"><a class="nav-link" href="index.php?section=02&amp;page=02&amp;sec_page=06"><i class="bi bi-envelope-at me-1"></i> Odběratelé</a></li>
</ul>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= rep_akce_e($error) ?></div>
<?php else: ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert"><i class="bi bi-check-circle me-2"></i><div><?= rep_akce_e($notice) ?></div></div>
    <?php endif; ?>

    <?php if ($tab === '03'): ?>
        <?php
        $pdfPath = rep_akce_primary_pdf_path($formOffer);
        $coverPath = rep_akce_primary_cover_path($formOffer);
        $legacyFlipAvailable = (int)$formOffer['id'] > 0 && rep_akce_flip_mobile_dir($formOffer) !== '';
        ?>
        <div class="card shadow mb-4" data-rep-akce>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold text-primary"><?= (int)$formOffer['id'] > 0 ? 'Editace akční nabídky' : 'Nová akční nabídka' ?></h6>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=02&amp;sec_page=01"><i class="bi bi-arrow-left me-1"></i> zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_offer">
                    <input type="hidden" name="id" value="<?= (int)$formOffer['id'] ?>">
                    <input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                    <input type="hidden" name="list_visible_filter" value="<?= $visibleFilter === null ? 'all' : (int)$visibleFilter ?>">
                    <input type="hidden" name="list_year" value="<?= (int)($year ?? 0) ?>">
                    <?php foreach ($typeIds as $typeId): ?><input type="hidden" name="types[]" value="<?= (int)$typeId ?>"><?php endforeach; ?>

                    <div class="col-md-4"><label for="akce_typ_id" class="form-label">Typ</label><select name="typ_id" id="akce_typ_id" class="form-select js-admin-single-picker" data-picker-title="Vybrat typ akční nabídky" data-picker-description="Vyberte jeden obsahový typ akční nabídky." data-picker-search-placeholder="Hledat podle názvu typu…" data-picker-empty-label="Bez typu"><option value="">Bez typu</option><?php foreach ($types as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (int)$type['id'] === (int)($formOffer['typ_id'] ?? 0) ? 'selected' : '' ?>><?= rep_akce_e($type['nazev_cz']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><label for="akce_datum_od" class="form-label">Datum od</label><input type="date" name="datum_od" id="akce_datum_od" class="form-control" value="<?= rep_akce_e(rep_akce_date_form($formOffer['datum_od'] ?? '')) ?>"></div>
                    <div class="col-md-2"><label for="akce_datum_do" class="form-label">Datum do</label><input type="date" name="datum_do" id="akce_datum_do" class="form-control" value="<?= rep_akce_e(rep_akce_date_form($formOffer['datum_do'] ?? '')) ?>"></div>
                    <div class="col-md-2"><label for="akce_viewer_mode" class="form-label">Prohlížení</label><select name="viewer_mode" id="akce_viewer_mode" class="form-select"><option value="pdf" <?= (string)($formOffer['viewer_mode'] ?? 'pdf') === 'pdf' ? 'selected' : '' ?>>PDF</option><option value="images" <?= (string)($formOffer['viewer_mode'] ?? '') === 'images' ? 'selected' : '' ?>>Obrázky</option><option value="legacy_flip" <?= (string)($formOffer['viewer_mode'] ?? '') === 'legacy_flip' ? 'selected' : '' ?>>Legacy flip</option></select></div>
                    <div class="col-md-2"><label class="form-label d-block">Stav</label><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="visible" id="akce_visible" value="1" <?= (int)($formOffer['visible'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="akce_visible">Zobrazovat</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="valid" id="akce_valid" value="1" <?= (int)($formOffer['valid'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="akce_valid">Validní</label></div><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="is_primary" id="akce_is_primary" value="1" <?= (int)($formOffer['is_primary'] ?? 0) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="akce_is_primary">Primární</label></div></div>

                    <div class="col-md-6">
                        <label for="akce_pdf_file" class="form-label">PDF ke stažení</label>
                        <?php if ((int)$formOffer['id'] > 0): ?>
                            <input type="file" id="akce_pdf_file" class="form-control" accept="application/pdf,.pdf" data-rep-akce-pdf-upload data-offer-id="<?= (int)$formOffer['id'] ?>" data-csrf-token="<?= rep_akce_e($csrfToken) ?>">
                            <div class="form-text" data-rep-akce-pdf-current><?php if ($pdfPath !== ''): ?><a href="<?= rep_akce_e(rep_akce_file_url($pdfPath)) ?>" target="_blank" rel="noopener">aktuální PDF</a><?php if ((string)($formOffer['pdf_original_name'] ?? '') !== ''): ?> - <?= rep_akce_e($formOffer['pdf_original_name']) ?><?php endif; ?><?php else: ?>PDF zatím není nahrané.<?php endif; ?></div>
                            <div class="small text-muted mt-2" data-rep-akce-pdf-status>PDF se nahrává samostatně po 4MB částech; jako uložené se označí až po kontrole celkové velikosti a zápisu k nabídce. Formulář není nutné ukládat.</div>
                        <?php else: ?>
                            <input type="file" id="akce_pdf_file" class="form-control" accept="application/pdf,.pdf" disabled>
                            <div class="form-text">PDF bude možné nahrát po prvním uložení akční nabídky.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6"><label for="akce_cover_image" class="form-label">Obálka / náhled</label><input type="file" name="cover_image" id="akce_cover_image" class="form-control" accept="image/jpeg,image/png,image/webp"><?php if ($coverPath !== ''): ?><div class="form-text"><a href="<?= rep_akce_e(rep_akce_file_url($coverPath)) ?>" target="_blank" rel="noopener">aktuální obrázek</a></div><?php endif; ?></div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">Stránky pro flip prohlížeč</div>
                                    <div class="small text-muted">Stránky lze vytvořit z nahraného PDF nebo ručně nahrát jako hotové obrázky. PDF zůstává také ke stažení.</div>
                                    <div class="small text-danger mt-1">Maximální kvalita se načítá ze systémové proměnné <code>rep_akce_page_image_quality</code> (aktuálně <?= (int)$pageImageQuality ?>), cílová velikost z <code>rep_akce_page_target_kb</code> (aktuálně <?= (int)$pageTargetKb ?> kB) a formát z <code>rep_akce_page_output_format</code> (aktuálně <?= rep_akce_e($pageOutputFormat) ?>). Převod pro každou stranu použije nejvyšší kvalitu, která se vejde do cílové velikosti. Výška zůstává 2400 px; pouze mimořádně složitá strana se může zmenšit, nejvýše na 1800 px.</div>
                                </div>
                                <span class="badge text-bg-secondary align-self-start" data-rep-akce-page-count>aktuálně <?= number_format($editPageCount, 0, ',', ' ') ?> stran</span>
                            </div>
                            <?php if ((int)$formOffer['id'] > 0): ?>
                                <div
                                    class="border rounded-3 bg-white p-3 mb-3"
                                    data-rep-akce-pdf-pages-converter
                                    data-offer-id="<?= (int)$formOffer['id'] ?>"
                                    data-csrf-token="<?= rep_akce_e($csrfToken) ?>"
                                    data-pdf-url="<?= $pdfPath !== '' ? rep_akce_e(rep_akce_file_url($pdfPath)) : '' ?>"
                                    data-page-format="<?= rep_akce_e($pageOutputFormat) ?>"
                                    data-page-quality="<?= (int)$pageImageQuality ?>"
                                    data-page-target-kb="<?= (int)$pageTargetKb ?>"
                                >
                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">Vytvořit stránky z nahraného PDF</div>
                                            <div class="small text-muted">PDF se zpracuje přímo v tomto prohlížeči po jedné stránce. Během převodu kartu nezavírejte.</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary" data-rep-akce-pdf-pages-start <?= $pdfPath === '' ? 'disabled' : '' ?>><i class="bi bi-file-earmark-image me-1"></i> vytvořit stránky</button>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="akce_pdf_replace_pages" value="1" data-rep-akce-pdf-replace-pages checked>
                                        <label class="form-check-label" for="akce_pdf_replace_pages">Nahradit stávající stránky výsledkem převodu</label>
                                    </div>
                                    <div class="progress mt-3" role="progressbar" aria-label="Průběh převodu PDF" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-rep-akce-pdf-pages-progress>
                                        <div class="progress-bar progress-bar-striped" data-rep-akce-pdf-pages-progress-bar>0 %</div>
                                    </div>
                                    <div class="small text-muted mt-2" data-rep-akce-pdf-pages-status><?= $pdfPath !== '' ? 'Připraveno k převodu aktuálního PDF.' : 'Nejdříve nahrajte PDF k této nabídce.' ?></div>
                                </div>

                                <div class="fw-semibold mb-1">Nebo nahrát hotové obrázky stran</div>
                                <label for="akce_page_images" class="form-label visually-hidden">Nahrát hotové obrázky stran</label>
                                <input type="file" id="akce_page_images" class="form-control" accept="image/jpeg,image/png,image/webp" multiple data-rep-akce-pages-upload data-offer-id="<?= (int)$formOffer['id'] ?>" data-csrf-token="<?= rep_akce_e($csrfToken) ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="replace_pages" id="akce_replace_pages" value="1" data-rep-akce-replace-pages>
                                    <label class="form-check-label" for="akce_replace_pages">Nahradit stávající stránky novými obrázky</label>
                                </div>
                                <div class="small text-muted mt-2" data-rep-akce-upload-status>Upload probíhá samostatně po souborech; formulář není nutné ukládat pro nahrání stránek.</div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">Stránky bude možné nahrát po prvním uložení akční nabídky.</div>
                            <?php endif; ?>
                            <?php if ($editPages !== []): ?>
                                <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2 mt-2">
                                    <?php foreach ($editPages as $page): ?>
                                        <div class="col">
                                            <a href="<?= rep_akce_e(rep_akce_file_url((string)$page['image_path'])) ?>" target="_blank" rel="noopener" class="d-block border rounded bg-white p-1 text-center small text-decoration-none">
                                                <img src="<?= rep_akce_e(rep_akce_file_url((string)$page['image_path'])) ?>" alt="Strana <?= (int)$page['poradi'] ?>" class="img-fluid rounded mb-1">
                                                <span>Strana <?= (int)$page['poradi'] ?></span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <ul class="nav nav-tabs admin-lang-tabs" id="akceLangTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="akce-cz-tab" data-bs-toggle="tab" data-bs-target="#akce-cz-pane" type="button" role="tab" aria-controls="akce-cz-pane" aria-selected="true">CZ</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="akce-en-tab" data-bs-toggle="tab" data-bs-target="#akce-en-pane" type="button" role="tab" aria-controls="akce-en-pane" aria-selected="false">EN</button>
                            </li>
                        </ul>
                        <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="akceLangTabsContent">
                            <div class="tab-pane fade show active" id="akce-cz-pane" role="tabpanel" aria-labelledby="akce-cz-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="akce_nazev_cz" class="form-label">Název CZ</label>
                                        <input type="text" name="nazev_cz" id="akce_nazev_cz" class="form-control" required value="<?= rep_akce_e($formOffer['nazev_cz'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="akce_text_cz" class="form-label">Text CZ</label>
                                        <textarea name="text_cz" id="akce_text_cz" class="form-control js-tinymce" rows="8" data-tinymce-height="320"><?= (string)($formOffer['text_cz'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="akce-en-pane" role="tabpanel" aria-labelledby="akce-en-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="akce_nazev_en" class="form-label">Název EN</label>
                                        <input type="text" name="nazev_en" id="akce_nazev_en" class="form-control" value="<?= rep_akce_e($formOffer['nazev_en'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="akce_text_en" class="form-label">Text EN</label>
                                        <textarea name="text_en" id="akce_text_en" class="form-control js-tinymce" rows="8" data-tinymce-height="320"><?= (string)($formOffer['text_en'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ((int)$formOffer['id'] > 0): ?>
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                <div><strong>Legacy ID:</strong> <?= rep_akce_e($formOffer['legacy_id'] ?? '') ?></div>
                                <div><strong>Legacy PDF:</strong> <?= rep_akce_e($formOffer['legacy_pdf_path'] ?? '') ?></div>
                                <div><strong>Legacy obrázky:</strong> <?= rep_akce_e($formOffer['legacy_image_dir'] ?? '') ?></div>
                                <div><strong>Legacy flip:</strong> <?= rep_akce_e($formOffer['legacy_flip_path'] ?? $formOffer['legacy_flip'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <?= admin_auto_translate_checkbox($formOffer ?? null, 'rep_akce_offer_auto_translate_en') ?>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Uložit</button>
                        <?php if ((int)$formOffer['id'] > 0): ?>
                            <a class="btn btn-outline-secondary" href="index.php?section=02&amp;page=02&amp;sec_page=05&amp;view=<?= (int)$formOffer['id'] ?>"><i class="bi bi-eye me-1"></i> Náhled</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ((int)$formOffer['id'] > 0): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if ((string)($formOffer['pdf_file'] ?? '') !== ''): ?><form method="post" data-rep-akce-confirm="Smazat nahrané PDF?"><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="remove_pdf"><input type="hidden" name="id" value="<?= (int)$formOffer['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-x me-1"></i> smazat PDF</button></form><?php endif; ?>
                        <?php if ((string)($formOffer['cover_image'] ?? '') !== ''): ?><form method="post" data-rep-akce-confirm="Smazat nahranou obálku?"><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="remove_cover"><input type="hidden" name="id" value="<?= (int)$formOffer['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-image-alt me-1"></i> smazat obálku</button></form><?php endif; ?>
                        <?php if ($legacyFlipAvailable): ?><form method="post" data-rep-akce-confirm="Převzít stránky z legacy _flip/files/mobile a nahradit aktuální stránky prohlížeče?"><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="import_flip_pages"><input type="hidden" name="id" value="<?= (int)$formOffer['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-in-down me-1"></i> převzít stránky z _flip</button></form><?php endif; ?>
                        <?php if ($editPageCount > 0): ?><form method="post" data-rep-akce-confirm="Smazat všechny stránky prohlížeče? PDF ani legacy zdroje se nesmažou."><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="remove_pages"><input type="hidden" name="id" value="<?= (int)$formOffer['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-images me-1"></i> smazat stránky</button></form><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($tab === '04'): ?>
        <div class="card shadow mb-4" data-rep-akce>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2"><h6 class="m-0 fw-bold text-primary"><?= (int)$formType['id'] > 0 ? 'Editace typu' : 'Nový typ' ?></h6><a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=02&amp;sec_page=02"><i class="bi bi-arrow-left me-1"></i> zpět na typy</a></div>
            <div class="card-body">
                <form method="post" class="row g-3" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="save_type"><input type="hidden" name="id" value="<?= (int)$formType['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                    <div class="col-md-2"><label for="typ_poradi" class="form-label">Pořadí</label><input type="number" name="poradi" id="typ_poradi" class="form-control" value="<?= (int)($formType['poradi'] ?? 0) ?>"></div>
                    <div class="col-md-2"><label for="typ_code" class="form-label">Kód</label><input type="text" name="code" id="typ_code" class="form-control" value="<?= rep_akce_e($formType['code'] ?? '') ?>"></div>
                    <div class="col-md-4"><label for="typ_nazev_cz" class="form-label">Název CZ</label><input type="text" name="nazev_cz" id="typ_nazev_cz" class="form-control" required value="<?= rep_akce_e($formType['nazev_cz'] ?? '') ?>"></div>
                    <div class="col-md-4"><label for="typ_nazev_en" class="form-label">Název EN</label><input type="text" name="nazev_en" id="typ_nazev_en" class="form-control" value="<?= rep_akce_e($formType['nazev_en'] ?? '') ?>"></div>
                    <div class="col-md-6"><label for="typ_color" class="form-label">Barva / CSS třída</label><input type="text" name="color" id="typ_color" class="form-control" value="<?= rep_akce_e($formType['color'] ?? '') ?>" placeholder="např. text-bg-qanto-velkoobchod"><div class="form-text">Stejný princip jako u štítků novinek, ukládá se CSS třída badge.</div></div>
                    <div class="col-md-3"><label for="typ_newsletter_group" class="form-label">Skupina pro odesílání</label><select name="newsletter_group" id="typ_newsletter_group" class="form-select"><option value="" <?= rep_akce_newsletter_group($formType['newsletter_group'] ?? '') === '' ? 'selected' : '' ?>>Neodesílat</option><option value="maloobchod" <?= rep_akce_newsletter_group($formType['newsletter_group'] ?? '') === 'maloobchod' ? 'selected' : '' ?>>Maloobchodní odběratelé</option><option value="velkoobchod" <?= rep_akce_newsletter_group($formType['newsletter_group'] ?? '') === 'velkoobchod' ? 'selected' : '' ?>>Velkoobchodní odběratelé</option><option value="obe_skupiny" <?= rep_akce_newsletter_group($formType['newsletter_group'] ?? '') === 'obe_skupiny' ? 'selected' : '' ?>>Maloobchodní i velkoobchodní odběratelé</option></select><div class="form-text">Určuje distribuční seznam nezávisle na veřejném typu letáku.</div></div>
                    <div class="col-md-3"><label class="form-label d-block">Náhled</label><span class="badge <?= rep_akce_e(rep_akce_badge_class((string)($formType['color'] ?? ''))) ?>"><?= rep_akce_e($formType['nazev_cz'] ?: 'Ukázka typu') ?></span></div>
                    <div class="col-12"><?= admin_auto_translate_checkbox($formType ?? null, 'rep_akce_type_auto_translate_en') ?></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="valid" id="typ_valid" value="1" <?= (int)($formType['valid'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="typ_valid">Validní</label></div></div>
                    <div class="col-12"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Uložit</button></div>
                </form>
            </div>
        </div>
    <?php elseif ($tab === '05' && $viewOffer): ?>
        <?php
        $pdfPath = rep_akce_primary_pdf_path($viewOffer);
        $coverPath = rep_akce_primary_cover_path($viewOffer);
        $viewerPages = rep_akce_viewer_pages($viewOffer, $viewPages);
        $viewerPagesJson = json_encode($viewerPages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($viewerPagesJson)) {
            $viewerPagesJson = '[]';
        }
        ?>
        <div class="card shadow mb-4" data-rep-akce>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2"><h6 class="m-0 fw-bold text-primary">Náhled akční nabídky</h6><div class="d-flex gap-2"><a class="btn btn-sm btn-success" href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;edit=<?= (int)$viewOffer['id'] ?>"><i class="bi bi-pencil-square me-1"></i> Upravit</a><a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=02&amp;sec_page=01"><i class="bi bi-arrow-left me-1"></i> zpět</a></div></div>
            <div class="card-body">
                <h2 class="h4 mb-1"><?= rep_akce_e($viewOffer['nazev_cz'] ?? '') ?></h2>
                <div class="text-muted mb-3"><?= rep_akce_e(rep_akce_date_www($viewOffer['datum_od'] ?? '')) ?><?php if (rep_akce_date_www($viewOffer['datum_do'] ?? '') !== ''): ?> - <?= rep_akce_e(rep_akce_date_www($viewOffer['datum_do'])) ?><?php endif; ?></div>
                <?php if ($viewerPages !== []): ?>
                    <div class="rep-akce-viewer" data-rep-akce-viewer data-pages="<?= rep_akce_e($viewerPagesJson) ?>">
                        <div class="rep-akce-viewer__toolbar">
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="first" title="První strana"><i class="bi bi-chevron-bar-left"></i></button>
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="prev" title="Předchozí strana"><i class="bi bi-chevron-left"></i></button>
                            <span class="rep-akce-viewer__page" data-akce-viewer-page>Strana 1 / <?= count($viewerPages) ?></span>
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="next" title="Další strana"><i class="bi bi-chevron-right"></i></button>
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="last" title="Poslední strana"><i class="bi bi-chevron-bar-right"></i></button>
                            <button type="button" class="btn btn-sm btn-light ms-lg-2" data-akce-viewer-action="zoom-out" title="Zmenšit"><i class="bi bi-zoom-out"></i></button>
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="zoom-in" title="Zvětšit"><i class="bi bi-zoom-in"></i></button>
                            <button type="button" class="btn btn-sm btn-light" data-akce-viewer-action="fullscreen" title="Celá obrazovka"><i class="bi bi-arrows-fullscreen"></i></button>
                            <?php if ($pdfPath !== ''): ?><a class="btn btn-sm btn-danger ms-lg-auto" href="<?= rep_akce_e(rep_akce_file_url($pdfPath)) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a><?php endif; ?>
                        </div>
                        <div class="rep-akce-viewer__body">
                            <div class="rep-akce-viewer__book-wrap">
                                <div class="rep-akce-viewer__book-stage" data-akce-viewer-stage>
                                    <div class="rep-akce-viewer__book" data-akce-viewer-book></div>
                                </div>
                            </div>
                            <div class="rep-akce-viewer__thumbs" data-akce-viewer-thumbs aria-label="Náhledy stran"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($coverPath !== ''): ?><img class="img-fluid rounded border mb-3" src="<?= rep_akce_e(rep_akce_file_url($coverPath)) ?>" alt="<?= rep_akce_e($viewOffer['nazev_cz'] ?? '') ?>"><?php endif; ?>
                    <?php if ($pdfPath !== ''): ?><div class="ratio ratio-4x3 mb-3"><iframe src="<?= rep_akce_e(rep_akce_file_url($pdfPath)) ?>" title="PDF"></iframe></div><a class="btn btn-outline-primary mb-3" href="<?= rep_akce_e(rep_akce_file_url($pdfPath)) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i> otevřít PDF</a><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($tab === '02'): ?>
        <div class="card shadow mb-4" data-rep-akce>
            <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary d-sm-inline">Typy akčních nabídek</h6><span class="d-none d-sm-inline-block ms-2">validní <?= number_format($validTypeCount, 0, ',', ' ') ?> / nevalidní <?= number_format($invalidTypeCount, 0, ',', ' ') ?></span></div>
            <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "asc" ]]' data-page-length="100"><thead class="table-dark"><tr><th>ID</th><th>Pořadí</th><th>Kód</th><th>Název CZ</th><th>Název EN</th><th>Barva</th><th>Odesílání</th><th>Akcí</th><th>Valid</th><th>Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead><tfoot class="table-light"><tr><th>ID</th><th>Pořadí</th><th>Kód</th><th>Název CZ</th><th>Název EN</th><th>Barva</th><th>Odesílání</th><th>Akcí</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot><tbody><?php foreach ($typeRows as $type): ?><tr><td><?= (int)$type['id'] ?></td><td><?= (int)$type['poradi'] ?></td><td><?= rep_akce_e($type['code'] ?? '') ?></td><td class="fw-semibold"><?= rep_akce_e($type['nazev_cz'] ?? '') ?></td><td><?= rep_akce_e($type['nazev_en'] ?? '') ?></td><td data-search="<?= rep_akce_e($type['color'] ?? '') ?>"><?= rep_akce_type_badge($type) ?><div class="small text-muted mt-1"><?= rep_akce_e($type['color'] ?? '') ?></div></td><td><?= rep_akce_e(rep_akce_newsletter_group_label($type['newsletter_group'] ?? '')) ?></td><td class="text-end"><?= (int)$type['akce_count'] ?></td><td class="text-center" data-search="<?= rep_akce_e(rep_akce_bool_label($type['valid'] ?? 0)) ?>"><?= rep_akce_bool_badge($type['valid'] ?? 0) ?></td><td data-order="<?= rep_akce_e($type['ts_u'] ?? '') ?>"><?= rep_akce_updated_cell($type) ?></td><td class="text-nowrap"><a href="index.php?section=02&amp;page=02&amp;sec_page=04&amp;edit=<?= (int)$type['id'] ?>" class="btn btn-sm btn-success" title="Upravit"><i class="bi bi-pencil-square"></i></a> <form method="post" class="d-inline" data-rep-akce-confirm="<?= (int)$type['valid'] === 1 ? 'Znevalidnit typ?' : 'Obnovit typ?' ?>"><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="<?= (int)$type['valid'] === 1 ? 'invalidate_type' : 'validate_type' ?>"><input type="hidden" name="id" value="<?= (int)$type['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>"><button type="submit" class="btn btn-<?= (int)$type['valid'] === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)$type['valid'] === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)$type['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
        </div>
    <?php else: ?>
        <?php
        $typeClearParams = ['section' => '02', 'page' => '02', 'sec_page' => '01', 'valid' => $valid];
        if ($visibleFilter !== null) { $typeClearParams['visible_filter'] = (string)$visibleFilter; }
        if ($year !== null && $year > 0) { $typeClearParams['year'] = (string)$year; }
        ?>
        <div class="card shadow mb-4" data-rep-akce>
            <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div class="flex-grow-1">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline">Akční nabídky</h6><span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($offers), 0, ',', ' ') ?> záznamů, nevalidní <?= number_format($invalidOfferCount, 0, ',', ' ') ?></span>
                    <div class="d-flex flex-wrap gap-2 align-items-start mt-2">
                        <div class="dropdown admin-filter-dropdown" data-admin-filter-dropdown><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"><?= $selectedTypeLabels === [] ? 'Typy: všechny' : 'Typy: ' . rep_akce_e(implode(', ', $selectedTypeLabels)) ?></button><form method="get" class="dropdown-menu admin-filter-menu p-0"><input type="hidden" name="section" value="02"><input type="hidden" name="page" value="02"><input type="hidden" name="sec_page" value="01"><input type="hidden" name="valid" value="<?= (int)$valid ?>"><?php if ($visibleFilter !== null): ?><input type="hidden" name="visible_filter" value="<?= (int)$visibleFilter ?>"><?php endif; ?><?php if ($year !== null && $year > 0): ?><input type="hidden" name="year" value="<?= (int)$year ?>"><?php endif; ?><div class="admin-filter-head"><div class="admin-filter-title">Typy akcí</div><input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat typ..." data-admin-filter-search></div><div class="admin-filter-options" data-admin-filter-options><?php foreach ($types as $type): ?><label class="admin-filter-option" data-admin-filter-item data-admin-filter-text="<?= rep_akce_e($type['nazev_cz'] ?? '') ?>"><input class="form-check-input" type="checkbox" name="types[]" value="<?= (int)$type['id'] ?>" <?= isset($typeMap[(int)$type['id']]) ? 'checked' : '' ?>><span class="admin-filter-option-label"><?= rep_akce_e($type['nazev_cz'] ?? '') ?></span><span class="admin-filter-count"><?= (int)$type['akce_count'] ?></span></label><?php endforeach; ?></div><div class="admin-filter-footer"><button type="submit" class="btn btn-sm btn-primary">Filtrovat</button><a class="btn btn-sm btn-outline-secondary" href="<?= rep_akce_e('index.php?' . http_build_query($typeClearParams, '', '&')) ?>">Vyčistit</a></div></form></div>
                        <div class="dropdown admin-filter-dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Rok: <?= $year !== null && $year > 0 ? (int)$year : 'vše' ?></button><div class="dropdown-menu admin-filter-menu admin-filter-menu-sm p-0"><div class="admin-filter-options"><a class="admin-filter-option admin-filter-link <?= $year === null ? 'is-active' : '' ?>" href="index.php?section=02&amp;page=02&amp;sec_page=01&amp;valid=<?= (int)$valid ?>"><span class="admin-filter-option-label">Všechny roky</span></a><?php foreach ($years as $yearRow): ?><a class="admin-filter-option admin-filter-link <?= (int)$yearRow['rok'] === (int)$year ? 'is-active' : '' ?>" href="index.php?section=02&amp;page=02&amp;sec_page=01&amp;valid=<?= (int)$valid ?>&amp;year=<?= (int)$yearRow['rok'] ?>"><span class="admin-filter-option-label"><?= (int)$yearRow['rok'] ?></span><span class="admin-filter-count"><?= (int)$yearRow['total'] ?></span></a><?php endforeach; ?></div></div></div>
                        <div class="dropdown admin-filter-dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Zobrazovat: <?= $visibleFilter === null ? 'vše' : rep_akce_e(rep_akce_bool_label($visibleFilter)) ?></button><div class="dropdown-menu admin-filter-menu admin-filter-menu-sm p-0"><div class="admin-filter-options"><?php foreach (['all' => 'Vše', '1' => 'Ano', '0' => 'Ne'] as $filterValue => $filterLabel): ?><?php $urlParams = ['section' => '02', 'page' => '02', 'sec_page' => '01', 'valid' => $valid]; foreach ($typeIds as $typeId) { $urlParams['types'][] = (string)$typeId; } if ($year !== null && $year > 0) { $urlParams['year'] = (string)$year; } if ($filterValue !== 'all') { $urlParams['visible_filter'] = $filterValue; } $isActive = ($filterValue === 'all' && $visibleFilter === null) || (string)$visibleFilter === $filterValue; ?><a class="admin-filter-option admin-filter-link <?= $isActive ? 'is-active' : '' ?>" href="<?= rep_akce_e('index.php?' . http_build_query($urlParams, '', '&')) ?>"><span class="admin-filter-option-label"><?= rep_akce_e($filterLabel) ?></span></a><?php endforeach; ?></div></div></div>
                    </div>
                </div>
            </div>
            <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 3, "desc" ], [ 0, "desc" ]]' data-page-length="100"><thead class="table-dark"><tr><th>ID</th><th>Typ</th><th>Název</th><th>Od</th><th>Do</th><th>PDF</th><th>Stran</th><th>Primární</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead><tfoot class="table-light"><tr><th>ID</th><th>Typ</th><th>Název</th><th>Od</th><th>Do</th><th>PDF</th><th>Stran</th><th>Primární</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot><tbody><?php foreach ($offers as $offer): ?><?php $pdfPath = rep_akce_primary_pdf_path($offer); ?><tr><td><?= (int)$offer['id'] ?></td><td data-search="<?= rep_akce_e($offer['typ_nazev_cz'] ?? '') ?>"><?php if ((string)($offer['typ_nazev_cz'] ?? '') !== ''): ?><span class="badge <?= rep_akce_e(rep_akce_badge_class((string)($offer['typ_color'] ?? ''))) ?>"><?= rep_akce_e($offer['typ_nazev_cz'] ?? '') ?></span><?php endif; ?></td><td class="fw-semibold"><?= rep_akce_e($offer['nazev_cz'] ?? '') ?><?php if ((int)($offer['legacy_id'] ?? 0) > 0): ?><div class="small text-muted">legacy #<?= (int)$offer['legacy_id'] ?></div><?php endif; ?></td><td data-order="<?= rep_akce_e($offer['datum_od'] ?? '') ?>"><?= rep_akce_e(rep_akce_date_www($offer['datum_od'] ?? '')) ?></td><td data-order="<?= rep_akce_e($offer['datum_do'] ?? '') ?>"><?= rep_akce_e(rep_akce_date_www($offer['datum_do'] ?? '')) ?></td><td class="text-center"><?php if ($pdfPath !== ''): ?><a href="<?= rep_akce_e(rep_akce_file_url($pdfPath)) ?>" target="_blank" rel="noopener" class="btn btn-danger btn-sm" title="Stáhnout PDF"><i class="bi bi-file-earmark-pdf"></i></a><?php endif; ?></td><td class="text-end"><?= (int)$offer['page_count'] ?></td><td class="text-center" data-search="<?= rep_akce_e(rep_akce_bool_label($offer['is_primary'] ?? 0)) ?>"><?= rep_akce_bool_badge($offer['is_primary'] ?? 0) ?></td><td class="text-center" data-search="<?= rep_akce_e(rep_akce_bool_label($offer['visible'] ?? 0)) ?>"><?= rep_akce_bool_badge($offer['visible'] ?? 0) ?></td><td class="text-center" data-search="<?= rep_akce_e(rep_akce_bool_label($offer['valid'] ?? 0)) ?>"><?= rep_akce_bool_badge($offer['valid'] ?? 0) ?></td><td data-order="<?= rep_akce_e($offer['ts_u'] ?? '') ?>"><?= rep_akce_updated_cell($offer) ?></td><td class="text-nowrap"><a href="index.php?section=02&amp;page=02&amp;sec_page=05&amp;view=<?= (int)$offer['id'] ?>" class="btn btn-primary btn-sm" title="Náhled"><i class="bi bi-eye"></i></a> <a href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;edit=<?= (int)$offer['id'] ?>" class="btn btn-sm btn-success" title="Upravit"><i class="bi bi-pencil-square"></i></a> <a href="index.php?section=02&amp;page=02&amp;sec_page=07&amp;send=<?= (int)$offer['id'] ?>" class="btn btn-sm btn-warning" title="Odeslat leták e-mailem"><i class="bi bi-envelope-paper"></i></a> <form method="post" class="d-inline" data-rep-akce-confirm="<?= (int)$offer['valid'] === 1 ? 'Znevalidnit akční nabídku?' : 'Obnovit akční nabídku?' ?>"><input type="hidden" name="csrf_token" value="<?= rep_akce_e($csrfToken) ?>"><input type="hidden" name="action" value="<?= (int)$offer['valid'] === 1 ? 'invalidate_offer' : 'validate_offer' ?>"><input type="hidden" name="id" value="<?= (int)$offer['id'] ?>"><input type="hidden" name="list_valid" value="<?= (int)$valid ?>"><input type="hidden" name="list_visible_filter" value="<?= $visibleFilter === null ? 'all' : (int)$visibleFilter ?>"><input type="hidden" name="list_year" value="<?= (int)($year ?? 0) ?>"><?php foreach ($typeIds as $typeId): ?><input type="hidden" name="types[]" value="<?= (int)$typeId ?>"><?php endforeach; ?><button type="submit" class="btn btn-<?= (int)$offer['valid'] === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)$offer['valid'] === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)$offer['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
        </div>
    <?php endif; ?>
<?php endif; ?>
