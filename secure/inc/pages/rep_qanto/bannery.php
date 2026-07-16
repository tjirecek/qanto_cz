<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_bannery.php';
require_once ROOT_DIR . '/functions/fun_rep_bannery.php';

global $pdo, $sec_page;

$tab = (string)($sec_page ?? '01');
if (!in_array($tab, ['01', '02'], true)) {
    $tab = '01';
}

$error = '';
$notice = '';
$csrfToken = (string)admin_session_get('rep_bannery_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_bannery_csrf_token', $csrfToken);
}

$valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
$positionFilter = rep_bannery_position((string)($_GET['position_key'] ?? ''));
if (!isset($_GET['position_key']) || (string)$_GET['position_key'] === '') {
    $positionFilter = '';
}
$validityFilter = rep_bannery_validity_filter($_GET['validity_filter'] ?? 'yes');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$banners = [];
$editBanner = null;
$autoSecondaryAds = [];

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění upravovat bannery.');
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_banner') {
            $editId = rep_bannery_save($pdo, $_POST, $_FILES);
            $notice = 'Banner byl uložen.';
            $tab = '02';
        } elseif ($action === 'invalidate_banner' || $action === 'validate_banner') {
            rep_bannery_set_valid($pdo, (int)($_POST['id'] ?? 0), $action === 'validate_banner' ? 1 : 0);
            $notice = $action === 'validate_banner' ? 'Banner byl obnoven.' : 'Banner byl znevalidněn.';
            $tab = '01';
        }

        $valid = isset($_POST['list_valid']) && (string)$_POST['list_valid'] === '0' ? 0 : 1;
        $postedPosition = (string)($_POST['list_position_key'] ?? '');
        $positionFilter = $postedPosition === '' ? '' : rep_bannery_position($postedPosition);
        $validityFilter = rep_bannery_validity_filter($_POST['list_validity_filter'] ?? 'yes');
    }

    if ($tab === '01') {
        $banners = rep_bannery_list($pdo, $valid, $positionFilter !== '' ? $positionFilter : null, $validityFilter);
        $autoSecondaryAds = function_exists('frontend_akce_auto_secondary_ads') ? frontend_akce_auto_secondary_ads('cz', 50) : [];
    } else {
        $editBanner = $editId > 0 ? rep_bannery_get($pdo, $editId) : null;
        if ($editId > 0 && !$editBanner) {
            throw new RuntimeException('Banner nebyl nalezen.');
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$positions = rep_bannery_positions();
$colors = rep_bannery_colors();
$validityFilters = rep_bannery_validity_filters();
$backgroundThemes = rep_bannery_background_themes();
$form = $editBanner ?? rep_bannery_default();
$positionFilterLabel = $positionFilter !== '' ? ($positions[$positionFilter] ?? $positionFilter) : 'všechny';
$validityFilterLabel = $validityFilters[$validityFilter] ?? 'Ano';
$validToggleParams = ['section' => '02', 'page' => '03', 'sec_page' => '01', 'valid' => $valid === 1 ? '0' : '1'];
if ($positionFilter !== '') {
    $validToggleParams['position_key'] = $positionFilter;
}
$validToggleParams['validity_filter'] = $validityFilter;
$validToggleUrl = 'index.php?' . http_build_query($validToggleParams, '', '&');
$validCount = ($pdo instanceof PDO) ? rep_bannery_count($pdo, 1) : 0;
$invalidCount = ($pdo instanceof PDO) ? rep_bannery_count($pdo, 0) : 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Bannery</h1>
        <div class="text-muted small">Project agenda qanto.cz: hlavní carousel a sekundární odkazy na homepage.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if ($tab === '01'): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= rep_bannery_e($validToggleUrl) ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
            <?php else: ?>
                <a href="<?= rep_bannery_e($validToggleUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
            <?php endif; ?>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">validní: <?= number_format($validCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">nevalidní: <?= number_format($invalidCount, 0, ',', ' ') ?></span>
        <?php if ($tab === '01'): ?>
            <a href="index.php?section=02&amp;page=03&amp;sec_page=02" class="btn btn-sm btn-primary shadow-sm">nový banner <i class="bi bi-plus-circle ms-1"></i></a>
        <?php endif; ?>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= rep_bannery_e($error) ?></div>
<?php else: ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert"><i class="bi bi-check-circle me-2"></i><div><?= rep_bannery_e($notice) ?></div></div>
    <?php endif; ?>

    <?php if ($tab === '02'): ?>
        <div class="card shadow mb-4" data-rep-bannery>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold text-primary"><?= (int)$form['id'] > 0 ? 'Editace banneru' : 'Nový banner' ?></h6>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=03&amp;sec_page=01"><i class="bi bi-arrow-left me-1"></i> zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= rep_bannery_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_banner">
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                    <input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                    <input type="hidden" name="list_position_key" value="<?= rep_bannery_e($positionFilter) ?>">
                    <input type="hidden" name="list_validity_filter" value="<?= rep_bannery_e($validityFilter) ?>">

                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <strong>Doporučené rozměry obrázků:</strong>
                            hlavní carousel <strong>540 × 300 px</strong> (poměr 1.8:1),
                            sekundární odkazy <strong>840 × 1188 px</strong> nebo stejný A4 portrait poměr (cca 1:1.414).
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="banner_position_key" class="form-label">Pozice</label>
                        <select name="position_key" id="banner_position_key" class="form-select" required>
                            <?php foreach ($positions as $key => $label): ?>
                                <option value="<?= rep_bannery_e($key) ?>" <?= (string)($form['position_key'] ?? '') === $key ? 'selected' : '' ?>><?= rep_bannery_e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="banner_poradi" class="form-label">Pořadí</label>
                        <input type="number" name="poradi" id="banner_poradi" class="form-control" value="<?= (int)($form['poradi'] ?? 0) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="banner_valid_to" class="form-label">Platnost do</label>
                        <input type="date" name="valid_to" id="banner_valid_to" class="form-control" value="<?= rep_bannery_e(rep_bannery_date_form($form['valid_to'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="banner_text_color" class="form-label">Barva textu</label>
                        <select name="text_color" id="banner_text_color" class="form-select">
                            <?php foreach ($colors as $key => $label): ?>
                                <option value="<?= rep_bannery_e($key) ?>" <?= (string)($form['text_color'] ?? 'dark') === $key ? 'selected' : '' ?>><?= rep_bannery_e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label for="banner_url" class="form-label">Odkaz</label>
                        <input type="text" name="url" id="banner_url" class="form-control" value="<?= rep_bannery_e($form['url'] ?? '') ?>" placeholder="/cz/akce nebo https://...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Stav</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible" id="banner_visible" value="1" <?= (int)($form['visible'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="banner_visible">Zobrazovat</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="valid" id="banner_valid" value="1" <?= (int)($form['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="banner_valid">Validní</label>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label for="banner_image" class="form-label">Obrázek</label>
                        <input type="file" name="image" id="banner_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Hlavní carousel: 540 × 300 px. Sekundární odkazy: A4 portrait poměr, doporučeně 840 × 1188 px.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Aktuální obrázek</label>
                        <?php if ((string)($form['image'] ?? '') !== ''): ?>
                            <a href="<?= rep_bannery_e(rep_bannery_file_url((string)$form['image'])) ?>" target="_blank" rel="noopener" class="d-inline-block mb-2">
                                <img src="<?= rep_bannery_e(rep_bannery_file_url((string)$form['image'])) ?>" alt="" class="rep-banner-form-preview img-thumbnail">
                            </a>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_image" id="banner_delete_image" value="1">
                                <label class="form-check-label" for="banner_delete_image">Smazat obrázek při uložení</label>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small">Bez obrázku.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Pozadí z katalogu</label>
                        <div class="small text-muted mb-2">Použije se pouze tehdy, když banner nemá nahraný obrázek.</div>
                        <div class="rep-banner-theme-grid">
                            <?php foreach ($backgroundThemes as $themeKey => $themeLabel): ?>
                                <?php $themeInputId = 'banner_theme_' . preg_replace('~[^a-z0-9_-]+~i', '-', $themeKey); ?>
                                <label class="rep-banner-theme-option">
                                    <input class="form-check-input" type="radio" name="background_theme" id="<?= rep_bannery_e($themeInputId) ?>" value="<?= rep_bannery_e($themeKey) ?>" <?= (string)($form['background_theme'] ?? 'brand-red') === $themeKey ? 'checked' : '' ?>>
                                    <span class="rep-banner-theme-preview rep-banner-theme-preview--<?= rep_bannery_e($themeKey) ?>"></span>
                                    <span class="rep-banner-theme-label"><?= rep_bannery_e($themeLabel) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <ul class="nav nav-tabs" id="bannerLangTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="banner-cz-tab" data-bs-toggle="tab" data-bs-target="#banner-cz" type="button" role="tab" aria-controls="banner-cz" aria-selected="true">CZ</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="banner-en-tab" data-bs-toggle="tab" data-bs-target="#banner-en" type="button" role="tab" aria-controls="banner-en" aria-selected="false">EN</button></li>
                        </ul>
                        <div class="tab-content border border-top-0 rounded-bottom p-3">
                            <div class="tab-pane fade show active" id="banner-cz" role="tabpanel" aria-labelledby="banner-cz-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="banner_popis_cz" class="form-label">Popis CZ</label>
                                        <textarea name="popis_cz" id="banner_popis_cz" class="form-control" rows="2" required><?= rep_bannery_e($form['popis_cz'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="banner_link_text_cz" class="form-label">Text odkazu CZ</label>
                                        <input type="text" name="link_text_cz" id="banner_link_text_cz" class="form-control" value="<?= rep_bannery_e($form['link_text_cz'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="banner-en" role="tabpanel" aria-labelledby="banner-en-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="banner_popis_en" class="form-label">Popis EN</label>
                                        <textarea name="popis_en" id="banner_popis_en" class="form-control" rows="2"><?= rep_bannery_e($form['popis_en'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="banner_link_text_en" class="form-label">Text odkazu EN</label>
                                        <input type="text" name="link_text_en" id="banner_link_text_en" class="form-control" value="<?= rep_bannery_e($form['link_text_en'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <?= admin_auto_translate_checkbox($form ?? null, 'rep_bannery_auto_translate_en') ?>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="index.php?section=02&amp;page=03&amp;sec_page=01" class="btn btn-outline-secondary">Zrušit</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Uložit</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow mb-4" data-rep-bannery>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="m-0 fw-bold text-primary">Výpis bannerů</h6>
                    <div class="dropdown admin-filter-dropdown" data-admin-filter-dropdown>
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            Pozice: <?= rep_bannery_e($positionFilterLabel) ?>
                        </button>
                        <div class="dropdown-menu admin-filter-menu admin-filter-menu-sm p-0">
                            <div class="admin-filter-head">
                                <div class="admin-filter-title">Pozice banneru</div>
                                <input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat pozici..." data-admin-filter-search aria-label="Hledat pozici">
                            </div>
                            <div class="admin-filter-options" data-admin-filter-options>
                                <a class="admin-filter-option admin-filter-link <?= $positionFilter === '' ? 'is-active' : '' ?>"
                                   href="index.php?section=02&amp;page=03&amp;sec_page=01&amp;valid=<?= (int)$valid ?>&amp;validity_filter=<?= rep_bannery_e($validityFilter) ?>"
                                   data-admin-filter-item
                                   data-admin-filter-text="vše všechny">
                                    <span class="admin-filter-option-label">Všechny pozice</span>
                                    <?php if ($positionFilter === ''): ?><i class="bi bi-check-lg text-primary"></i><?php endif; ?>
                                </a>
                                <?php foreach ($positions as $key => $label): ?>
                                    <a class="admin-filter-option admin-filter-link <?= $positionFilter === $key ? 'is-active' : '' ?>"
                                       href="index.php?section=02&amp;page=03&amp;sec_page=01&amp;valid=<?= (int)$valid ?>&amp;validity_filter=<?= rep_bannery_e($validityFilter) ?>&amp;position_key=<?= rep_bannery_e($key) ?>"
                                       data-admin-filter-item
                                       data-admin-filter-text="<?= rep_bannery_e($label) ?>">
                                        <span class="admin-filter-option-label"><?= rep_bannery_e($label) ?></span>
                                        <?php if ($positionFilter === $key): ?><i class="bi bi-check-lg text-primary"></i><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown admin-filter-dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Platné: <?= rep_bannery_e($validityFilterLabel) ?>
                        </button>
                        <div class="dropdown-menu admin-filter-menu admin-filter-menu-sm p-0">
                            <div class="admin-filter-head">
                                <div class="admin-filter-title">Platné podle data</div>
                            </div>
                            <div class="admin-filter-options">
                                <?php foreach ($validityFilters as $filterKey => $filterLabel): ?>
                                    <?php
                                    $validityUrlParams = ['section' => '02', 'page' => '03', 'sec_page' => '01', 'valid' => $valid, 'validity_filter' => $filterKey];
                                    if ($positionFilter !== '') {
                                        $validityUrlParams['position_key'] = $positionFilter;
                                    }
                                    ?>
                                    <a class="admin-filter-option admin-filter-link <?= $validityFilter === $filterKey ? 'is-active' : '' ?>"
                                       href="<?= rep_bannery_e('index.php?' . http_build_query($validityUrlParams, '', '&')) ?>">
                                        <span class="admin-filter-option-label"><?= rep_bannery_e($filterLabel) ?></span>
                                        <?php if ($validityFilter === $filterKey): ?><i class="bi bi-check-lg text-primary"></i><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-3">
                    <strong>Rozměry bannerů:</strong>
                    hlavní carousel 540 × 300 px (poměr 1.8:1), sekundární odkazy A4 portrait, doporučeně 840 × 1188 px.
                </div>
                <div class="alert alert-danger small mb-3">
                    <strong>Automatické sekundární odkazy z akčních nabídek:</strong>
                    <?= number_format(count($autoSecondaryAds), 0, ',', ' ') ?>.
                    <span class="d-block mt-1">
                        Zobrazuje se vždy primární aktuální nebo nadcházející akční nabídka v dané kategorii akcí.
                        Pokud je v kategorii více primárních nabídek, bere se TOP 1 podle nejvyšší platnosti do.
                    </span>
                    <?php if ($autoSecondaryAds !== []): ?>
                        <span class="text-muted d-block mt-1">
                            Kategorie:
                            <?php foreach ($autoSecondaryAds as $index => $item): ?><?= $index > 0 ? ', ' : '' ?><?= rep_bannery_e((string)($item['type_label'] ?: $item['title'])) ?><?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "asc" ], [ 2, "asc" ]]' data-page-length="100">
                        <thead class="table-dark"><tr><th>ID</th><th>Pozice</th><th>Pořadí</th><th>Náhled</th><th>Popis</th><th>Text odkazu</th><th>Odkaz</th><th>Barva</th><th>Pozadí</th><th>Platnost do</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                        <tfoot class="table-light"><tr><th>ID</th><th>Pozice</th><th>Pořadí</th><th>Náhled</th><th>Popis</th><th>Text odkazu</th><th>Odkaz</th><th>Barva</th><th>Pozadí</th><th>Platnost do</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                        <tbody>
                        <?php foreach ($banners as $banner): ?>
                            <?php $positionLabel = $positions[(string)$banner['position_key']] ?? (string)$banner['position_key']; ?>
                            <tr>
                                <td><?= (int)$banner['id'] ?></td>
                                <td><?= rep_bannery_e($positionLabel) ?></td>
                                <td class="text-end"><?= (int)$banner['poradi'] ?></td>
                                <td class="text-center">
                                    <?php if ((string)($banner['image'] ?? '') !== ''): ?>
                                        <a href="<?= rep_bannery_e(rep_bannery_file_url((string)$banner['image'])) ?>" target="_blank" rel="noopener"><img src="<?= rep_bannery_e(rep_bannery_file_url((string)$banner['image'])) ?>" alt="" class="rep-banner-thumb img-thumbnail"></a>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= rep_bannery_e($banner['popis_cz'] ?? '') ?><?php if ((string)($banner['popis_en'] ?? '') !== ''): ?><div class="small text-muted"><?= rep_bannery_e($banner['popis_en']) ?></div><?php endif; ?></td>
                                <td><?= rep_bannery_e($banner['link_text_cz'] ?? '') ?></td>
                                <td><?php if ((string)($banner['url'] ?? '') !== ''): ?><a href="<?= rep_bannery_e((string)$banner['url']) ?>" target="_blank" rel="noopener"><?= rep_bannery_e((string)$banner['url']) ?></a><?php endif; ?></td>
                                <td><?= rep_bannery_e($colors[(string)$banner['text_color']] ?? (string)$banner['text_color']) ?></td>
                                <td><?= rep_bannery_e($backgroundThemes[(string)($banner['background_theme'] ?? '')] ?? (string)($banner['background_theme'] ?? '')) ?></td>
                                <td data-order="<?= rep_bannery_e($banner['valid_to'] ?? '') ?>"><?= rep_bannery_e(rep_bannery_date_www($banner['valid_to'] ?? '')) ?></td>
                                <td class="text-center" data-search="<?= rep_bannery_e(rep_bannery_bool_label($banner['visible'] ?? 0)) ?>"><?= rep_bannery_bool_badge($banner['visible'] ?? 0) ?></td>
                                <td class="text-center" data-search="<?= rep_bannery_e(rep_bannery_bool_label($banner['valid'] ?? 0)) ?>"><?= rep_bannery_bool_badge($banner['valid'] ?? 0) ?></td>
                                <td data-order="<?= rep_bannery_e($banner['ts_u'] ?? '') ?>"><?= rep_bannery_updated_cell($banner) ?></td>
                                <td class="text-nowrap">
                                    <a href="index.php?section=02&amp;page=03&amp;sec_page=02&amp;edit=<?= (int)$banner['id'] ?>" class="btn btn-sm btn-success" title="Upravit"><i class="bi bi-pencil-square"></i></a>
                                    <form method="post" class="d-inline" data-confirm="<?= (int)$banner['valid'] === 1 ? 'Znevalidnit banner?' : 'Obnovit banner?' ?>">
                                        <input type="hidden" name="csrf_token" value="<?= rep_bannery_e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="<?= (int)$banner['valid'] === 1 ? 'invalidate_banner' : 'validate_banner' ?>">
                                        <input type="hidden" name="id" value="<?= (int)$banner['id'] ?>">
                                        <input type="hidden" name="list_valid" value="<?= (int)$valid ?>">
                                        <input type="hidden" name="list_position_key" value="<?= rep_bannery_e($positionFilter) ?>">
                                        <input type="hidden" name="list_validity_filter" value="<?= rep_bannery_e($validityFilter) ?>">
                                        <button type="submit" class="btn btn-<?= (int)$banner['valid'] === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)$banner['valid'] === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)$banner['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
