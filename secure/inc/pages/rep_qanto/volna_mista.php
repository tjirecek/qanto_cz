<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_volna_mista.php';

global $pdo, $sec_page;

if (!function_exists('rep_volna_mista_admin_url')) {
    /** @param array<string, mixed> $params */
    function rep_volna_mista_admin_url(string $tab, array $params = []): string
    {
        $query = array_merge([
            'section' => '02',
            'page' => '01',
            'sec_page' => $tab,
        ], $params);

        return 'index.php?' . http_build_query($query, '', '&');
    }
}

if (!function_exists('rep_volna_mista_filter_hidden_inputs')) {
    /** @param array<int, int> $typeIds */
    function rep_volna_mista_filter_hidden_inputs(array $typeIds, int $valid, ?int $visibleFilter = null): string
    {
        $html = '<input type="hidden" name="list_valid" value="' . (int)$valid . '">';
        $html .= '<input type="hidden" name="list_visible_filter" value="' . ($visibleFilter === null ? 'all' : (int)$visibleFilter) . '">';
        foreach ($typeIds as $typeId) {
            $html .= '<input type="hidden" name="types[]" value="' . (int)$typeId . '">';
        }

        return $html;
    }
}

$tab = in_array((string)($sec_page ?? '01'), ['01', '02', '03', '04', '05', '06', '07'], true) ? (string)$sec_page : '01';
$error = '';
$notice = '';
$typeIds = rep_volna_mista_parse_ids($_GET['types'] ?? []);
$valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
$visibleValue = (string)($_GET['visible'] ?? 'all');
$visibleFilter = in_array($visibleValue, ['0', '1'], true) ? (int)$visibleValue : null;
$viewApplicationId = (int)($_GET['view'] ?? 0);
$types = [];
$typeRows = [];
$jobs = [];
$applications = [];
$contacts = [];
$viewApplication = null;
$viewApplicationAttachments = [];
$applicationTypeCounts = [];
$csrfToken = (string)admin_session_get('rep_volna_mista_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_volna_mista_csrf_token', $csrfToken);
}

$formJob = [
    'id' => 0,
    'typ_id' => 0,
    'pocet' => 1,
    'nazev_cz' => '',
    'nazev_en' => '',
    'popis_cz' => '',
    'popis_en' => '',
    'legacy_contact_id' => null,
    'kontakt_lide_id' => null,
    'visible' => 1,
    'valid' => 1,
];
$formType = [
    'id' => 0,
    'stredisko_kod' => 0,
    'nazev_cz' => '',
    'nazev_en' => '',
    'popis_cz' => '',
    'popis_en' => '',
    'email_up' => '',
    'visible' => 1,
    'valid' => 1,
];

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění upravovat volná místa.');
        }

        $typeIds = rep_volna_mista_parse_ids($_POST['types'] ?? []);
        $valid = isset($_POST['list_valid']) && (string)$_POST['list_valid'] === '0' ? 0 : 1;
        $visiblePost = (string)($_POST['list_visible_filter'] ?? 'all');
        $visibleFilter = in_array($visiblePost, ['0', '1'], true) ? (int)$visiblePost : null;
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save_job') {
            rep_volna_mista_save_job($pdo, $_POST);
            $notice = 'Pracovní místo bylo uloženo.';
            $tab = '01';
        } elseif ($action === 'save_type') {
            rep_volna_mista_save_type($pdo, $_POST);
            $notice = 'Skupina pracovních míst byla uložena.';
            $tab = '02';
        } elseif ($action === 'invalidate_job' || $action === 'validate_job') {
            rep_volna_mista_set_valid($pdo, 'rep_volna_mista', (int)($_POST['id'] ?? 0), $action === 'validate_job' ? 1 : 0);
            $notice = $action === 'validate_job' ? 'Pracovní místo bylo obnoveno.' : 'Pracovní místo bylo znevalidněno.';
            $tab = '01';
        } elseif ($action === 'invalidate_type' || $action === 'validate_type') {
            rep_volna_mista_set_valid($pdo, 'rep_volna_mista_typ', (int)($_POST['id'] ?? 0), $action === 'validate_type' ? 1 : 0);
            $notice = $action === 'validate_type' ? 'Skupina byla obnovena.' : 'Skupina byla znevalidněna.';
            $tab = '02';
        } elseif ($action === 'invalidate_application' || $action === 'validate_application') {
            rep_volna_mista_set_valid($pdo, 'rep_volna_mista_dotaznik', (int)($_POST['id'] ?? 0), $action === 'validate_application' ? 1 : 0);
            $notice = $action === 'validate_application' ? 'Dotazník byl obnoven.' : 'Dotazník byl znevalidněn.';
            $tab = '03';
        } elseif ($action === 'send_job_up_new' || $action === 'send_job_up_cancel') {
            $result = rep_volna_mista_send_up_notice($pdo, (int)($_POST['id'] ?? 0), $action === 'send_job_up_cancel' ? 'cancel' : 'new');
            $notice = 'E-mail pro ÚP byl odeslán na ' . (string)($result['recipient'] ?? 'zadaný e-mail') . '.';
            $tab = '01';
        }
    }

    $types = rep_volna_mista_types($pdo, null, 'name');
    $typeRows = rep_volna_mista_types($pdo, $valid, 'name');
    $contacts = rep_volna_mista_contacts($pdo);
    $jobs = rep_volna_mista_jobs($pdo, $typeIds, $valid, $visibleFilter);
    $applications = rep_volna_mista_applications($pdo, $typeIds, $valid);
    $applicationTypeCounts = rep_volna_mista_application_type_counts($pdo, $valid);

    if ($tab === '05') {
        $editJob = rep_volna_mista_job($pdo, (int)($_GET['edit'] ?? 0));
        if (!$editJob) {
            throw new RuntimeException('Pracovní místo nebylo nalezeno.');
        }
        $formJob = array_merge($formJob, $editJob);
    }
    if ($tab === '07') {
        $editType = rep_volna_mista_type($pdo, (int)($_GET['edit'] ?? 0));
        if (!$editType) {
            throw new RuntimeException('Skupina pracovních míst nebyla nalezena.');
        }
        $formType = array_merge($formType, $editType);
    }
    if ($viewApplicationId > 0) {
        $viewApplication = rep_volna_mista_application($pdo, $viewApplicationId);
        if ($viewApplication) {
            $viewApplicationAttachments = rep_volna_mista_application_attachments($pdo, (int)$viewApplication['id']);
        }
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

$visibleFilterLabel = $visibleFilter === null ? 'Vše' : ($visibleFilter === 1 ? 'Ano' : 'Ne');
$jobFilterClearUrl = rep_volna_mista_admin_url('01', ['valid' => $valid]);
$applicationFilterClearUrl = rep_volna_mista_admin_url('03', ['valid' => $valid]);
$validToggleParams = ['valid' => $valid === 1 ? 0 : 1];
foreach ($typeIds as $typeId) {
    $validToggleParams['types'][] = $typeId;
}
if ($visibleFilter !== null) {
    $validToggleParams['visible'] = $visibleFilter;
}
$validToggleUrl = rep_volna_mista_admin_url($tab, $validToggleParams);
$jobValidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista', 1) : 0;
$jobInvalidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista', 0) : 0;
$typeValidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista_typ', 1) : 0;
$typeInvalidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista_typ', 0) : 0;
$applicationValidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista_dotaznik', 1) : 0;
$applicationInvalidCount = ($pdo instanceof PDO) ? rep_volna_mista_count($pdo, 'rep_volna_mista_dotaznik', 0) : 0;
$activeCount = $tab === '02' || $tab === '06' || $tab === '07' ? count($typeRows) : ($tab === '03' ? count($applications) : count($jobs));
$activeValidCount = $tab === '02' || $tab === '06' || $tab === '07' ? $typeValidCount : ($tab === '03' ? $applicationValidCount : $jobValidCount);
$activeInvalidCount = $tab === '02' || $tab === '06' || $tab === '07' ? $typeInvalidCount : ($tab === '03' ? $applicationInvalidCount : $jobInvalidCount);
$selectedContactId = (int)($formJob['kontakt_lide_id'] ?? 0);
$selectedContactLabel = 'bez přiřazené osoby';
foreach ($contacts as $contact) {
    if ((int)$contact['id'] === $selectedContactId) {
        $selectedContactLabel = rep_volna_mista_contact_label($contact);
        break;
    }
}
$visibleFilterUrls = [
    ['label' => 'Vše', 'value' => null, 'active' => $visibleFilter === null],
    ['label' => 'Ano', 'value' => 1, 'active' => $visibleFilter === 1],
    ['label' => 'Ne', 'value' => 0, 'active' => $visibleFilter === 0],
];
foreach ($visibleFilterUrls as $index => $filter) {
    $params = ['valid' => $valid];
    foreach ($typeIds as $typeId) {
        $params['types'][] = $typeId;
    }
    if ($filter['value'] !== null) {
        $params['visible'] = $filter['value'];
    }
    $visibleFilterUrls[$index]['url'] = rep_volna_mista_admin_url('01', $params);
}
$applicationListUrl = rep_volna_mista_admin_url('03', ['valid' => $valid] + ($typeIds !== [] ? ['types' => $typeIds] : []));
$detailFields = [
    'dot_adresa' => 'Adresa',
    'dot_birthday' => 'Datum narození',
    'dot_vzdelani' => 'Vzdělání',
    'dot_rp' => 'ŘP',
    'dot_jazyk' => 'Jazyky',
    'dot_pc' => 'PC',
    'dot_predchozizam' => 'Předchozí zaměstnavatel',
    'dot_funkcezam' => 'Funkce',
    'dot_delkazam' => 'Délka zaměstnání',
    'dot_pracdoba' => 'Pracovní doba',
    'dot_plat' => 'Plat',
    'dot_koureni' => 'Kouření',
    'dot_rejstrik' => 'Rejstřík',
    'dot_zdravstav' => 'Zdravotní stav',
    'dot_zaliby' => 'Záliby',
    'dot_onas' => 'Jak se dozvěděl/a',
    'dot_prinos' => 'Přínos',
    'dot_profzivot' => 'Profesní životopis',
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Volná místa</h1>
        <div class="text-muted small">Project agenda qanto.cz: pracovní pozice, skupiny a přijaté dotazníky.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if ($valid === 1): ?>
            <a href="<?= rep_volna_mista_e($validToggleUrl) ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
        <?php else: ?>
            <a href="<?= rep_volna_mista_e($validToggleUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">načteno: <?= number_format($activeCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-success shadow-sm">validní: <?= number_format($activeValidCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-secondary shadow-sm">nevalidní: <?= number_format($activeInvalidCount, 0, ',', ' ') ?></span>
        <?php if ($tab === '01'): ?>
            <a class="btn btn-sm btn-primary shadow-sm" href="index.php?section=02&amp;page=01&amp;sec_page=04"><i class="bi bi-plus-lg me-1"></i> Nová pozice</a>
        <?php elseif ($tab === '02'): ?>
            <a class="btn btn-sm btn-primary shadow-sm" href="index.php?section=02&amp;page=01&amp;sec_page=06"><i class="bi bi-plus-lg me-1"></i> Nová skupina</a>
        <?php endif; ?>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item"><a class="nav-link <?= in_array($tab, ['01', '04', '05'], true) ? 'active' : '' ?>" href="index.php?section=02&amp;page=01&amp;sec_page=01"><i class="bi bi-briefcase me-1"></i> Pozice</a></li>
    <li class="nav-item"><a class="nav-link <?= in_array($tab, ['02', '06', '07'], true) ? 'active' : '' ?>" href="index.php?section=02&amp;page=01&amp;sec_page=02"><i class="bi bi-folder me-1"></i> Skupiny</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === '03' ? 'active' : '' ?>" href="index.php?section=02&amp;page=01&amp;sec_page=03"><i class="bi bi-card-checklist me-1"></i> Dotazníky</a></li>
</ul>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= rep_volna_mista_e($error) ?></div>
<?php else: ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert"><i class="bi bi-check-circle me-2"></i><div><?= rep_volna_mista_e($notice) ?></div></div>
    <?php endif; ?>

    <?php if ($tab === '04' || $tab === '05'): ?>
        <div class="card shadow mb-4" data-rep-volna-mista>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold text-primary"><?= $tab === '05' ? 'Editace pozice' : 'Nová pozice' ?></h6>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=01&amp;sec_page=01&amp;valid=<?= (int)$valid ?>"><i class="bi bi-arrow-left me-1"></i> zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_job">
                    <input type="hidden" name="id" value="<?= (int)$formJob['id'] ?>">
                    <input type="hidden" name="legacy_contact_id" value="<?= (int)($formJob['legacy_contact_id'] ?? 0) ?>">
                    <?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?>

                    <div class="col-md-6">
                        <label for="job_typ_id" class="form-label">Skupina</label>
                        <select name="typ_id" id="job_typ_id" class="form-select" required>
                            <option value="">Vyber skupinu</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int)$type['id'] ?>" <?= (int)($formJob['typ_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>><?= rep_volna_mista_e($type['nazev_cz']) ?><?php if ((int)$type['stredisko_kod'] > 0): ?> (<?= (int)$type['stredisko_kod'] ?>)<?php endif; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="job_pocet" class="form-label">Počet míst</label>
                        <input type="number" min="0" name="pocet" id="job_pocet" class="form-control" value="<?= (int)($formJob['pocet'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="visible" id="job_visible" value="1" <?= (int)($formJob['visible'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="job_visible">zobrazovat</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="valid" id="job_valid" value="1" <?= (int)($formJob['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="job_valid">valid</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="job_nazev_cz" class="form-label">Název CZ</label>
                        <input type="text" name="nazev_cz" id="job_nazev_cz" class="form-control" required value="<?= rep_volna_mista_e($formJob['nazev_cz'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="job_nazev_en" class="form-label">Název EN</label>
                        <input type="text" name="nazev_en" id="job_nazev_en" class="form-control" value="<?= rep_volna_mista_e($formJob['nazev_en'] ?? '') ?>">
                    </div>
                    <div class="col-12" data-rep-volna-mista-contact-picker>
                        <label class="form-label">Kontaktní osoba</label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge text-bg-light border text-dark" data-rep-volna-mista-contact-label><?= rep_volna_mista_e($selectedContactLabel) ?></span>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#repVolnaMistaContactModal"><i class="bi bi-person-lines-fill me-1"></i> Vybrat osobu</button>
                        </div>

                        <div class="modal fade" id="repVolnaMistaContactModal" tabindex="-1" aria-labelledby="repVolnaMistaContactModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="repVolnaMistaContactModalLabel">Vybrat kontaktní osobu</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="admin-filter-head">
                                            <input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat osobu..." data-admin-filter-search aria-label="Hledat osobu">
                                        </div>
                                        <div class="admin-filter-options" data-admin-filter-options>
                                            <label class="admin-filter-option" data-admin-filter-item data-admin-filter-text="bez kontaktu">
                                                <input class="form-check-input" type="radio" name="kontakt_lide_id" value="0" data-contact-label="bez přiřazené osoby" <?= $selectedContactId === 0 ? 'checked' : '' ?>>
                                                <span class="admin-filter-option-label">Bez přiřazené osoby</span>
                                            </label>
                                            <?php foreach ($contacts as $contact): ?>
                                                <?php $contactLabel = rep_volna_mista_contact_label($contact); ?>
                                                <label class="admin-filter-option" data-admin-filter-item data-admin-filter-text="<?= rep_volna_mista_e($contactLabel) ?>">
                                                    <input class="form-check-input" type="radio" name="kontakt_lide_id" value="<?= (int)$contact['id'] ?>" data-contact-label="<?= rep_volna_mista_e($contactLabel) ?>" <?= $selectedContactId === (int)$contact['id'] ? 'checked' : '' ?>>
                                                    <span class="admin-filter-option-label"><?= rep_volna_mista_e($contact['jmeno']) ?><span class="text-muted ms-1"><?= rep_volna_mista_e($contact['funkce_cz'] ?? '') ?></span></span>
                                                    <span class="admin-filter-count"><?= rep_volna_mista_e($contact['skupina_nazev'] ?? '') ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Použít</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <ul class="nav nav-tabs" id="jobTextTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="job-text-cz-tab" data-bs-toggle="tab" data-bs-target="#job-text-cz" type="button" role="tab">CZ popis</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="job-text-en-tab" data-bs-toggle="tab" data-bs-target="#job-text-en" type="button" role="tab">EN popis</button></li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3">
                            <div class="tab-pane fade show active" id="job-text-cz" role="tabpanel" aria-labelledby="job-text-cz-tab">
                                <textarea name="popis_cz" id="job_popis_cz" class="form-control js-tinymce" rows="10" data-tinymce-height="320"><?= rep_volna_mista_e($formJob['popis_cz'] ?? '') ?></textarea>
                            </div>
                            <div class="tab-pane fade" id="job-text-en" role="tabpanel" aria-labelledby="job-text-en-tab">
                                <textarea name="popis_en" id="job_popis_en" class="form-control js-tinymce" rows="10" data-tinymce-height="320"><?= rep_volna_mista_e($formJob['popis_en'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <?= admin_auto_translate_checkbox($formJob ?? null, 'rep_volna_mista_job_auto_translate_en') ?>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Uložit pozici</button>
                        <a class="btn btn-outline-secondary" href="index.php?section=02&amp;page=01&amp;sec_page=01&amp;valid=<?= (int)$valid ?>">Zpět bez uložení</a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($tab === '06' || $tab === '07'): ?>
        <div class="card shadow mb-4" data-rep-volna-mista>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="m-0 fw-bold text-primary"><?= $tab === '07' ? 'Editace skupiny' : 'Nová skupina' ?></h6>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;valid=<?= (int)$valid ?>"><i class="bi bi-arrow-left me-1"></i> zpět na výpis</a>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_type">
                    <input type="hidden" name="id" value="<?= (int)$formType['id'] ?>">
                    <?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?>
                    <div class="col-md-5"><label for="type_nazev_cz" class="form-label">Název CZ</label><input type="text" name="nazev_cz" id="type_nazev_cz" class="form-control" required value="<?= rep_volna_mista_e($formType['nazev_cz'] ?? '') ?>"></div>
                    <div class="col-md-5"><label for="type_nazev_en" class="form-label">Název EN</label><input type="text" name="nazev_en" id="type_nazev_en" class="form-control" value="<?= rep_volna_mista_e($formType['nazev_en'] ?? '') ?>"></div>
                    <div class="col-md-2"><label for="type_stredisko" class="form-label">Středisko</label><input type="number" min="0" name="stredisko_kod" id="type_stredisko" class="form-control" value="<?= (int)($formType['stredisko_kod'] ?? 0) ?>"></div>
                    <div class="col-md-6"><label for="type_email_up" class="form-label">E-mail ÚP</label><input type="email" name="email_up" id="type_email_up" class="form-control" value="<?= rep_volna_mista_e($formType['email_up'] ?? '') ?>"></div>
                    <div class="col-md-6 d-flex align-items-end gap-3">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="visible" id="type_visible" value="1" <?= (int)($formType['visible'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="type_visible">zobrazovat</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="valid" id="type_valid" value="1" <?= (int)($formType['valid'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="type_valid">valid</label></div>
                    </div>
                    <div class="col-md-6"><label for="type_popis_cz" class="form-label">Popis CZ / adresa</label><textarea name="popis_cz" id="type_popis_cz" class="form-control" rows="3"><?= rep_volna_mista_e($formType['popis_cz'] ?? '') ?></textarea></div>
                    <div class="col-md-6"><label for="type_popis_en" class="form-label">Popis EN</label><textarea name="popis_en" id="type_popis_en" class="form-control" rows="3"><?= rep_volna_mista_e($formType['popis_en'] ?? '') ?></textarea></div>
                    <div class="col-12"><?= admin_auto_translate_checkbox($formType ?? null, 'rep_volna_mista_type_auto_translate_en') ?></div>
                    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Uložit skupinu</button><a class="btn btn-outline-secondary" href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;valid=<?= (int)$valid ?>">Zpět bez uložení</a></div>
                </form>
            </div>
        </div>

    <?php elseif ($tab === '02'): ?>
        <div class="card shadow mb-4" data-rep-volna-mista>
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div><h6 class="m-0 fw-bold text-primary d-sm-inline">Skupiny pracovních míst</h6><span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($typeRows), 0, ',', ' ') ?> záznamů</span></div>
                <a class="btn btn-sm btn-primary" href="index.php?section=02&amp;page=01&amp;sec_page=06"><i class="bi bi-plus-lg me-1"></i> Nová skupina</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-page-length="500">
                        <thead class="table-dark"><tr><th class="no-filter">ID</th><th class="text-filter dt-autocomplete">Název</th><th class="text-filter">E-mail ÚP</th><th class="select-filter">Středisko</th><th class="select-filter">Pozic</th><th class="select-filter">Zobrazovat</th><th class="select-filter">Valid</th><th class="text-filter">Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                        <tfoot class="table-light"><tr><th>ID</th><th>Název</th><th>E-mail ÚP</th><th>Středisko</th><th>Pozic</th><th>Zobrazovat</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                        <tbody>
                        <?php foreach ($typeRows as $type): ?>
                            <tr>
                                <td><?= (int)$type['id'] ?></td>
                                <td class="fw-semibold"><?= rep_volna_mista_e($type['nazev_cz']) ?><?php if ((string)($type['nazev_en'] ?? '') !== ''): ?><div class="small text-muted">EN: <?= rep_volna_mista_e($type['nazev_en']) ?></div><?php endif; ?></td>
                                <td><?= rep_volna_mista_e($type['email_up'] ?? '') ?></td>
                                <td class="text-end"><?= (int)$type['stredisko_kod'] ?></td>
                                <td class="text-end"><?= (int)$type['pozice_count'] ?></td>
                                <td class="text-center" data-search="<?= rep_volna_mista_bool_label($type['visible']) ?>"><span class="badge text-bg-<?= (int)$type['visible'] === 1 ? 'success' : 'secondary' ?>"><?= rep_volna_mista_bool_label($type['visible']) ?></span></td>
                                <td class="text-center" data-search="<?= rep_volna_mista_bool_label($type['valid']) ?>"><span class="badge text-bg-<?= (int)$type['valid'] === 1 ? 'success' : 'secondary' ?>"><?= rep_volna_mista_bool_label($type['valid']) ?></span></td>
                                <td data-order="<?= rep_volna_mista_e($type['ts_u'] ?? '') ?>"><?= rep_volna_mista_updated_cell($type) ?></td>
                                <td class="text-center text-nowrap">
                                    <a class="btn btn-success btn-sm" title="Upravit" href="index.php?section=02&amp;page=01&amp;sec_page=07&amp;edit=<?= (int)$type['id'] ?>&amp;valid=<?= (int)$valid ?>"><i class="bi bi-pencil-square"></i></a>
                                    <form method="post" class="d-inline" data-confirm="Opravdu změnit validitu skupiny?"><input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$type['id'] ?>"><input type="hidden" name="action" value="<?= (int)$type['valid'] === 1 ? 'invalidate_type' : 'validate_type' ?>"><?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?><button type="submit" class="btn btn-<?= (int)$type['valid'] === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)$type['valid'] === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)$type['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($tab === '03'): ?>
        <?php if ($viewApplication): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center"><h6 class="m-0 fw-bold text-primary">Detail dotazníku #<?= (int)$viewApplication['id'] ?></h6><a class="btn btn-sm btn-outline-secondary" href="<?= rep_volna_mista_e($applicationListUrl) ?>">Zavřít detail</a></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-3"><strong>Datum</strong><br><?= rep_volna_mista_e(rep_volna_mista_format_date($viewApplication['dot_datum'] ?? '')) ?></div>
                    <div class="col-md-3"><strong>Uchazeč</strong><br><?= rep_volna_mista_e($viewApplication['dot_name'] ?? '') ?></div>
                    <div class="col-md-3"><strong>E-mail</strong><br><?= rep_volna_mista_e($viewApplication['dot_email'] ?? '') ?></div>
                    <div class="col-md-3"><strong>Mobil</strong><br><?= rep_volna_mista_e($viewApplication['dot_mobil'] ?? '') ?></div>
                    <div class="col-md-6"><strong>Pozice</strong><br><?= rep_volna_mista_e($viewApplication['dot_pozice'] ?? '') ?><div class="small text-muted"><?= rep_volna_mista_e($viewApplication['misto_nazev_cz'] ?? '') ?></div></div>
                    <div class="col-md-6"><strong>Skupina</strong><br><?= rep_volna_mista_e($viewApplication['typ_nazev_cz'] ?? '') ?></div>
                    <div class="col-md-6"><strong>Přílohy</strong><br>
                        <?php if ($viewApplicationAttachments !== []): ?>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php foreach ($viewApplicationAttachments as $attachment): ?>
                                    <?php $attachmentUrl = rep_volna_mista_application_attachment_url($viewApplication, $attachment); ?>
                                    <?php if ($attachmentUrl !== ''): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= rep_volna_mista_e($attachmentUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i><?= rep_volna_mista_e(rep_volna_mista_application_attachment_row_label($attachment)) ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <?php $attachmentUrl = rep_volna_mista_application_attachment_url($viewApplication); ?>
                            <?php if ($attachmentUrl !== ''): ?><a class="btn btn-sm btn-outline-primary mt-1" href="<?= rep_volna_mista_e($attachmentUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i><?= rep_volna_mista_e(rep_volna_mista_application_attachment_label($viewApplication)) ?></a><?php else: ?><span class="text-muted">bez přílohy</span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php foreach ($detailFields as $field => $label): ?><div class="col-md-6"><strong><?= rep_volna_mista_e($label) ?></strong><br><div class="text-break"><?= nl2br(rep_volna_mista_e($viewApplication[$field] ?? '')) ?></div></div><?php endforeach; ?>
                </div></div>
            </div>
        <?php endif; ?>
        <div class="card shadow mb-4" data-rep-volna-mista>
            <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div class="flex-grow-1">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline">Přijaté dotazníky</h6><span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($applications), 0, ',', ' ') ?> záznamů</span>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <div class="dropdown admin-filter-dropdown" data-admin-filter-dropdown>
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="bi bi-funnel me-1"></i> Skupiny<?php if ($typeIds !== []): ?> <span class="badge text-bg-primary ms-1"><?= count($typeIds) ?></span><?php endif; ?></button>
                            <form method="get" class="dropdown-menu admin-filter-menu p-0">
                                <input type="hidden" name="section" value="02"><input type="hidden" name="page" value="01"><input type="hidden" name="sec_page" value="03"><input type="hidden" name="valid" value="<?= (int)$valid ?>">
                                <div class="admin-filter-head"><div class="admin-filter-title">Skupiny dotazníků</div><input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat skupinu..." data-admin-filter-search aria-label="Hledat skupinu"></div>
                                <div class="admin-filter-options" data-admin-filter-options>
                                    <?php foreach ($types as $type): ?>
                                        <label class="admin-filter-option" data-admin-filter-item data-admin-filter-text="<?= rep_volna_mista_e(trim((string)$type['nazev_cz'] . ' ' . (string)$type['stredisko_kod'])) ?>"><input class="form-check-input" type="checkbox" name="types[]" value="<?= (int)$type['id'] ?>" <?= isset($typeMap[(int)$type['id']]) ? 'checked' : '' ?>><span class="admin-filter-option-label"><?= rep_volna_mista_e($type['nazev_cz']) ?><?php if ((int)$type['stredisko_kod'] > 0): ?> <span class="text-muted">(<?= (int)$type['stredisko_kod'] ?>)</span><?php endif; ?></span><span class="admin-filter-count"><?= (int)($applicationTypeCounts[(int)$type['id']] ?? 0) ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="admin-filter-footer"><a class="btn btn-sm btn-link text-decoration-none" href="<?= rep_volna_mista_e($applicationFilterClearUrl) ?>">Zrušit filtr</a><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Použít</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($selectedTypeLabels !== []): ?><div class="alert alert-light border small mb-3">Aktivní filtr: skupiny <?= rep_volna_mista_e(implode(', ', $selectedTypeLabels)) ?></div><?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "desc" ]]' data-page-length="500">
                        <thead class="table-dark"><tr><th class="no-filter">ID</th><th class="text-filter">Datum</th><th class="text-filter dt-autocomplete">Skupina</th><th class="text-filter dt-autocomplete">Pozice</th><th class="text-filter dt-autocomplete">Uchazeč</th><th class="text-filter">E-mail</th><th class="text-filter">Mobil</th><th class="select-filter">Valid</th><th class="text-filter">Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                        <tfoot class="table-light"><tr><th>ID</th><th>Datum</th><th>Skupina</th><th>Pozice</th><th>Uchazeč</th><th>E-mail</th><th>Mobil</th><th>Valid</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                        <tbody>
                        <?php foreach ($applications as $application): ?>
                            <?php $attachmentUrl = rep_volna_mista_application_attachment_url($application); $attachmentCount = max((int)($application['attachment_count'] ?? 0), $attachmentUrl !== '' ? 1 : 0); ?>
                            <tr><td><?= (int)$application['id'] ?></td><td class="text-nowrap" data-order="<?= rep_volna_mista_e($application['dot_datum'] ?? '') ?>"><?= rep_volna_mista_e(rep_volna_mista_format_date($application['dot_datum'] ?? '')) ?></td><td><?= rep_volna_mista_e($application['typ_nazev_cz'] ?? '') ?></td><td><?= rep_volna_mista_e($application['dot_pozice'] ?? '') ?><?php if ((string)($application['misto_nazev_cz'] ?? '') !== ''): ?><div class="small text-muted"><?= rep_volna_mista_e($application['misto_nazev_cz']) ?></div><?php endif; ?><?php if ($attachmentCount > 0): ?><div class="small text-muted"><i class="bi bi-paperclip"></i> <?= $attachmentCount === 1 ? '1 příloha' : (int)$attachmentCount . ' příloh' ?></div><?php endif; ?></td><td class="fw-semibold"><?= rep_volna_mista_e($application['dot_name'] ?? '') ?></td><td><?= rep_volna_mista_e($application['dot_email'] ?? '') ?></td><td><?= rep_volna_mista_e($application['dot_mobil'] ?? '') ?></td><td class="text-center" data-search="<?= rep_volna_mista_bool_label($application['valid']) ?>"><span class="badge text-bg-<?= (int)$application['valid'] === 1 ? 'success' : 'secondary' ?>"><?= rep_volna_mista_bool_label($application['valid']) ?></span></td><td data-order="<?= rep_volna_mista_e($application['ts_u'] ?? '') ?>"><?= rep_volna_mista_updated_cell($application) ?></td><td class="text-center text-nowrap"><a class="btn btn-success btn-sm" title="Zobrazit" href="<?= rep_volna_mista_e($applicationListUrl . '&view=' . (int)$application['id']) ?>"><i class="bi bi-eye"></i></a> <a class="btn btn-danger btn-sm" title="Stáhnout PDF" href="<?= rep_volna_mista_e(rep_volna_mista_application_pdf_url((int)$application['id'])) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i></a><?php if ($attachmentUrl !== ''): ?> <a class="btn btn-outline-primary btn-sm" title="Stáhnout první přílohu" href="<?= rep_volna_mista_e($attachmentUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i></a><?php endif; ?> <form method="post" class="d-inline" data-confirm="Opravdu změnit validitu dotazníku?"><input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$application['id'] ?>"><input type="hidden" name="action" value="<?= (int)$application['valid'] === 1 ? 'invalidate_application' : 'validate_application' ?>"><?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?><button type="submit" class="btn btn-<?= (int)$application['valid'] === 1 ? 'danger' : 'success' ?> btn-sm"><i class="bi bi-<?= (int)$application['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="card shadow mb-4" data-rep-volna-mista>
            <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div class="flex-grow-1">
                    <h6 class="m-0 fw-bold text-primary d-sm-inline">Pracovní pozice</h6><span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($jobs), 0, ',', ' ') ?> záznamů</span>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <div class="dropdown admin-filter-dropdown" data-admin-filter-dropdown>
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="bi bi-funnel me-1"></i> Skupiny<?php if ($typeIds !== []): ?> <span class="badge text-bg-primary ms-1"><?= count($typeIds) ?></span><?php endif; ?></button>
                            <form method="get" class="dropdown-menu admin-filter-menu p-0" data-rep-volna-mista-filter>
                                <input type="hidden" name="section" value="02"><input type="hidden" name="page" value="01"><input type="hidden" name="sec_page" value="01"><input type="hidden" name="valid" value="<?= (int)$valid ?>"><?php if ($visibleFilter !== null): ?><input type="hidden" name="visible" value="<?= (int)$visibleFilter ?>"><?php endif; ?>
                                <div class="admin-filter-head"><div class="admin-filter-title">Skupiny pracovních míst</div><input type="search" class="form-control form-control-sm admin-filter-search" placeholder="Hledat skupinu..." data-admin-filter-search aria-label="Hledat skupinu"></div>
                                <div class="admin-filter-options" data-admin-filter-options>
                                    <?php foreach ($types as $type): ?>
                                        <label class="admin-filter-option" data-admin-filter-item data-admin-filter-text="<?= rep_volna_mista_e(trim((string)$type['nazev_cz'] . ' ' . (string)$type['stredisko_kod'])) ?>"><input class="form-check-input" type="checkbox" name="types[]" value="<?= (int)$type['id'] ?>" <?= isset($typeMap[(int)$type['id']]) ? 'checked' : '' ?>><span class="admin-filter-option-label"><?= rep_volna_mista_e($type['nazev_cz']) ?><?php if ((int)$type['stredisko_kod'] > 0): ?> <span class="text-muted">(<?= (int)$type['stredisko_kod'] ?>)</span><?php endif; ?></span><span class="admin-filter-count"><?= (int)$type['pozice_count'] ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="admin-filter-footer"><a class="btn btn-sm btn-link text-decoration-none" href="<?= rep_volna_mista_e($jobFilterClearUrl) ?>">Zrušit filtr</a><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Použít</button></div>
                            </form>
                        </div>
                        <div class="dropdown admin-filter-dropdown">
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="bi bi-eye me-1"></i> Zobrazovat: <?= rep_volna_mista_e($visibleFilterLabel) ?></button>
                            <div class="dropdown-menu admin-filter-menu admin-filter-menu-sm p-0">
                                <div class="admin-filter-head"><div class="admin-filter-title">Zobrazovat</div></div>
                                <div class="admin-filter-options">
                                    <?php foreach ($visibleFilterUrls as $filter): ?><a class="admin-filter-option admin-filter-link <?= $filter['active'] ? 'is-active' : '' ?>" href="<?= rep_volna_mista_e($filter['url']) ?>"><span class="admin-filter-option-label"><?= rep_volna_mista_e($filter['label']) ?></span><?php if ($filter['active']): ?><i class="bi bi-check-lg text-primary"></i><?php endif; ?></a><?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="btn btn-sm btn-primary" href="index.php?section=02&amp;page=01&amp;sec_page=04"><i class="bi bi-plus-lg me-1"></i> Nová pozice</a>
            </div>
            <div class="card-body">
                <?php if ($selectedTypeLabels !== [] || $visibleFilter !== null): ?><div class="alert alert-light border small mb-3">Aktivní filtr: <?php if ($selectedTypeLabels !== []): ?>skupiny <?= rep_volna_mista_e(implode(', ', $selectedTypeLabels)) ?><?php endif; ?><?php if ($visibleFilter !== null): ?><?= $selectedTypeLabels !== [] ? '; ' : '' ?>zobrazovat <?= rep_volna_mista_e($visibleFilterLabel) ?><?php endif; ?></div><?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-page-length="500">
                        <thead class="table-dark"><tr><th class="no-filter">ID</th><th class="text-filter dt-autocomplete">Název</th><th class="text-filter dt-autocomplete">Skupina</th><th class="select-filter">Počet</th><th class="select-filter">Zobrazovat</th><th class="select-filter">Valid</th><th class="text-filter dt-autocomplete">Kontakt</th><th class="text-filter">Upraveno</th><th class="no-sort no-filter">Akce</th></tr></thead>
                        <tfoot class="table-light"><tr><th>ID</th><th>Název</th><th>Skupina</th><th>Počet</th><th>Zobrazovat</th><th>Valid</th><th>Kontakt</th><th>Upraveno</th><th>Akce</th></tr></tfoot>
                        <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr><td><?= (int)$job['id'] ?></td><td class="fw-semibold"><?= rep_volna_mista_e($job['nazev_cz']) ?><?php if ((string)($job['nazev_en'] ?? '') !== ''): ?><div class="small text-muted">EN: <?= rep_volna_mista_e($job['nazev_en']) ?></div><?php endif; ?></td><td><?= rep_volna_mista_e($job['typ_nazev_cz'] ?? '') ?><?php if ((int)($job['typ_stredisko_kod'] ?? 0) > 0): ?><div class="small text-muted">středisko <?= (int)$job['typ_stredisko_kod'] ?></div><?php endif; ?></td><td class="text-end"><?= (int)$job['pocet'] ?></td><td class="text-center" data-search="<?= rep_volna_mista_bool_label($job['visible']) ?>"><span class="badge text-bg-<?= (int)$job['visible'] === 1 ? 'success' : 'secondary' ?>"><?= rep_volna_mista_bool_label($job['visible']) ?></span></td><td class="text-center" data-search="<?= rep_volna_mista_bool_label($job['valid']) ?>"><span class="badge text-bg-<?= (int)$job['valid'] === 1 ? 'success' : 'secondary' ?>"><?= rep_volna_mista_bool_label($job['valid']) ?></span></td><td><?php if ((string)($job['kontakt_jmeno'] ?? '') !== ''): ?><?= rep_volna_mista_e($job['kontakt_jmeno']) ?><?php else: ?><span class="text-muted">bez kontaktu</span><?php endif; ?></td><td data-order="<?= rep_volna_mista_e($job['ts_u'] ?? '') ?>"><?= rep_volna_mista_updated_cell($job) ?></td><td class="text-center text-nowrap"><form method="post" class="d-inline" data-confirm="Opravdu odeslat oznámení o novém pracovním místě na ÚP?"><input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$job['id'] ?>"><input type="hidden" name="action" value="send_job_up_new"><?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?><button type="submit" class="btn btn-info btn-sm" title="Odeslat nové místo na ÚP"><i class="bi bi-send"></i></button></form> <form method="post" class="d-inline" data-confirm="Opravdu odeslat oznámení o zrušení pracovního místa na ÚP?"><input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$job['id'] ?>"><input type="hidden" name="action" value="send_job_up_cancel"><?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?><button type="submit" class="btn btn-warning btn-sm" title="Odeslat zrušení místa na ÚP"><i class="bi bi-x-octagon"></i></button></form> <a class="btn btn-success btn-sm" title="Upravit" href="index.php?section=02&amp;page=01&amp;sec_page=05&amp;edit=<?= (int)$job['id'] ?>"><i class="bi bi-pencil-square"></i></a> <form method="post" class="d-inline" data-confirm="Opravdu změnit validitu pracovního místa?"><input type="hidden" name="csrf_token" value="<?= rep_volna_mista_e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$job['id'] ?>"><input type="hidden" name="action" value="<?= (int)$job['valid'] === 1 ? 'invalidate_job' : 'validate_job' ?>"><?= rep_volna_mista_filter_hidden_inputs($typeIds, $valid, $visibleFilter) ?><button type="submit" class="btn btn-<?= (int)$job['valid'] === 1 ? 'danger' : 'success' ?> btn-sm" title="<?= (int)$job['valid'] === 1 ? 'Smazat' : 'Obnovit' ?>"><i class="bi bi-<?= (int)$job['valid'] === 1 ? 'trash' : 'arrow-counterclockwise' ?>"></i></button></form></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
