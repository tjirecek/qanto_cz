<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_zavoz_obce.php';

global $pdo, $sec_page;

$csrfToken = (string)admin_session_get('rep_zavoz_obce_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_zavoz_obce_csrf_token', $csrfToken);
}

$requestedSubpage = (string)($sec_page ?? '01');
$activeSubpage = in_array($requestedSubpage, ['01', '02', '03'], true) ? $requestedSubpage : '01';
$notice = '';
$error = '';

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }

        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        $obecId = (int)($_POST['obec_id'] ?? 0);
        $okresId = (int)($_POST['okres_id'] ?? 0);

        if ($action === 'import_xlsx') {
            if (!isset($_FILES['xlsx_file']) || !is_uploaded_file((string)($_FILES['xlsx_file']['tmp_name'] ?? ''))) {
                throw new RuntimeException('Vyber XLSX soubor pro import.');
            }

            $result = rep_zavoz_import_xlsx(
                $pdo,
                (string)$_FILES['xlsx_file']['tmp_name'],
                (string)($_FILES['xlsx_file']['name'] ?? 'import.xlsx')
            );

            $notice = 'Import dokončen: spárováno ' . (int)$result['rows_matched']
                . ', nespárováno ' . (int)$result['rows_unmatched']
                . ', nejednoznačné ' . (int)$result['rows_ambiguous']
                . ', nové obce ' . (int)$result['obce_inserted']
                . ', aktualizované obce ' . (int)$result['obce_updated']
                . ', prodej ' . number_format((float)$result['prodej_total'], 2, ',', ' ') . '.';
        } elseif ($action === 'set_status' && $id > 0) {
            rep_zavoz_set_status($pdo, $id, (string)($_POST['status'] ?? 'review'));
            $notice = 'Stav obce byl změněn.';
        } elseif ($action === 'set_obec_status' && $obecId > 0) {
            rep_zavoz_set_obec_status($pdo, $obecId, (string)($_POST['status'] ?? 'review'));
            $notice = 'Stav obce byl změněn.';
        } elseif ($action === 'save_obec' && $obecId > 0) {
            rep_zavoz_save_by_obec($pdo, $obecId, $_POST);
            $notice = 'Obec byla uložena.';
        } elseif ($action === 'save_okres' && $okresId > 0) {
            rep_zavoz_save_okres($pdo, $okresId, $_POST);
            $notice = 'Okres byl uložen.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$counts = ($pdo instanceof PDO) ? rep_zavoz_counts($pdo) : [];
$rows = ($pdo instanceof PDO) ? rep_zavoz_all_obce_rows($pdo, 7000) : [];
$okresRows = ($activeSubpage === '03' && $pdo instanceof PDO) ? rep_zavoz_okres_rows($pdo) : [];
$ozOptions = ($activeSubpage === '03' && $pdo instanceof PDO) ? rep_zavoz_fetch_oz_options($pdo) : [];
$mapPoints = $activeSubpage === '02' ? rep_zavoz_map_points($rows) : [];
$mapAreas = ($activeSubpage === '02' && $pdo instanceof PDO) ? rep_zavoz_map_areas($pdo) : ['type' => 'FeatureCollection', 'features' => []];
$mapPointsJson = json_encode($mapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$mapAreasJson = json_encode($mapAreas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$baseUrl = rep_zavoz_page_url();
$statuses = rep_zavoz_ui_statuses();
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Závozové obce</h1>
        <div class="text-muted small">Project agenda qanto.cz: číselník obcí, import PSČ + prodej, ruční stavy a mapový náhled.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <button type="button" class="btn btn-sm btn-light shadow-sm active" data-rep-zavoz-table-filter="all">číselník obcí: <?= number_format((int)($counts['obce'] ?? 0), 0, ',', ' ') ?></button>
        <span class="badge text-bg-light border align-self-center py-2">okresy: <?= number_format((int)($counts['okresy'] ?? 0), 0, ',', ' ') ?></span>
        <span class="badge text-bg-light border align-self-center py-2">PSČ vazby: <?= number_format((int)($counts['psc'] ?? 0), 0, ',', ' ') ?></span>
        <button type="button" class="btn btn-sm btn-success shadow-sm" data-rep-zavoz-table-filter="served">obsluhujeme: <?= number_format((int)($counts['served'] ?? 0), 0, ',', ' ') ?></button>
        <button type="button" class="btn btn-sm btn-secondary shadow-sm" data-rep-zavoz-table-filter="not_served">neobsluhujeme: <?= number_format((int)($counts['not_served'] ?? 0), 0, ',', ' ') ?></button>
        <button type="button" class="btn btn-sm btn-warning shadow-sm" data-rep-zavoz-table-filter="review">ke kontrole: <?= number_format((int)($counts['review'] ?? 0), 0, ',', ' ') ?></button>
        <button type="button" class="btn btn-sm btn-danger shadow-sm" data-rep-zavoz-table-filter="excluded">vyloučeno: <?= number_format((int)($counts['excluded'] ?? 0), 0, ',', ' ') ?></button>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '01' ? 'active' : '' ?>" href="<?= rep_zavoz_e(rep_zavoz_page_url(['sec_page' => '01'])) ?>">
            <i class="bi bi-list-ul me-1"></i> Obce
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '02' ? 'active' : '' ?>" href="<?= rep_zavoz_e(rep_zavoz_page_url(['sec_page' => '02'])) ?>">
            <i class="bi bi-map me-1"></i> Mapa
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeSubpage === '03' ? 'active' : '' ?>" href="<?= rep_zavoz_e(rep_zavoz_page_url(['sec_page' => '03'])) ?>">
            <i class="bi bi-diagram-3 me-1"></i> Okresy
        </a>
    </li>
</ul>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= rep_zavoz_e($notice) ?></div>
    </div>
<?php endif; ?>

    <div class="modal fade" id="repZavozMapStatusModal" tabindex="-1" aria-labelledby="repZavozMapStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= rep_zavoz_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="set_obec_status">
                    <input type="hidden" name="obec_id" data-rep-zavoz-modal-obec-id value="">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="repZavozMapStatusModalLabel" data-rep-zavoz-modal-title>Obec</h5>
                            <div class="small text-muted" data-rep-zavoz-modal-meta></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rep_zavoz_modal_status" class="form-label">Stav</label>
                            <select name="status" id="rep_zavoz_modal_status" class="form-select" data-rep-zavoz-modal-status>
                                <?php foreach ($statuses as $statusKey => $statusConfig): ?>
                                    <option value="<?= rep_zavoz_e($statusKey) ?>"><?= rep_zavoz_e((string)$statusConfig['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="small text-muted" data-rep-zavoz-modal-sales></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zavřít</button>
                        <button type="submit" class="btn btn-primary">Uložit stav</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php if ($activeSubpage === '03'): ?>
    <div class="modal fade" id="repZavozOkresModal" tabindex="-1" aria-labelledby="repZavozOkresModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= rep_zavoz_e($csrfToken) ?>">
                <input type="hidden" name="action" value="save_okres">
                <input type="hidden" name="okres_id" data-rep-zavoz-okres-modal-id value="">
                <input type="hidden" name="obchodni_zastupce_id" data-rep-zavoz-okres-modal-oz value="">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="repZavozOkresModalLabel" data-rep-zavoz-okres-modal-title>Okres</h5>
                            <div class="small text-muted">
                                <span data-rep-zavoz-okres-modal-meta></span>
                                <span class="d-block">Kliknutím na řádek vyberete obchodního zástupce pro oblast.</span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rep_zavoz_okres_modal_search" class="form-label">Hledat obchodního zástupce</label>
                            <input
                                type="search"
                                id="rep_zavoz_okres_modal_search"
                                class="form-control"
                                placeholder="Hledat podle jména nebo e-mailu"
                                data-rep-zavoz-okres-oz-search
                            >
                            <div class="form-text">Použije se jako fallback. Kontakt nastavený přímo u obce má prioritu.</div>
                        </div>

                        <div class="small text-muted mb-2">Vybráno: <strong data-rep-zavoz-okres-modal-oz-label>bez kontaktu</strong></div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                <tr>
                                    <th>Jméno</th>
                                    <th>E-mail</th>
                                    <th class="text-end">Akce</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    data-rep-zavoz-okres-oz-choice
                                    data-oz-id=""
                                    data-oz-label="bez kontaktu"
                                >
                                    <td class="fw-semibold">bez kontaktu</td>
                                    <td class="text-muted">Okres nebude mít fallback kontakt.</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">vybrat</button>
                                    </td>
                                </tr>
                                <?php foreach ($ozOptions as $oz): ?>
                                    <?php
                                    $optionLabel = trim((string)($oz['jmeno'] ?? ''));
                                    $optionEmail = trim((string)($oz['email'] ?? ''));
                                    if ($optionLabel === '' && $optionEmail !== '') {
                                        $optionLabel = $optionEmail;
                                    }
                                    ?>
                                    <tr
                                        data-rep-zavoz-okres-oz-choice
                                        data-oz-id="<?= (int)$oz['id'] ?>"
                                        data-oz-label="<?= rep_zavoz_e($optionLabel) ?>"
                                    >
                                        <td class="fw-semibold"><?= rep_zavoz_e($optionLabel) ?></td>
                                        <td><?= rep_zavoz_e($optionEmail) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-primary">vybrat</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning mb-3 d-none" data-rep-zavoz-okres-oz-empty>
                            Nenalezen žádný obchodní zástupce pro zadaný filtr.
                        </div>

                        <div>
                            <label for="rep_zavoz_okres_modal_note" class="form-label">Poznámka</label>
                            <input
                                type="text"
                                name="note_internal"
                                id="rep_zavoz_okres_modal_note"
                                class="form-control"
                                data-rep-zavoz-okres-modal-note
                            >
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zavřít</button>
                        <button type="submit" class="btn btn-primary">Uložit kontakt</button>
                    </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div><?= rep_zavoz_e($error) ?></div>
    </div>
<?php endif; ?>

<?php if ((int)($counts['obce'] ?? 0) === 0): ?>
    <div class="alert alert-warning">
        Číselník obcí je prázdný. Import PSČ + PRODEJ začne párovat až po naplnění referenčních tabulek `rep_cr_obce` a `rep_cr_obce_psc`.
    </div>
<?php endif; ?>

<?php if ($activeSubpage === '01'): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="m-0 fw-bold text-primary">Import PSČ a prodeje</h6>
            <span class="small text-muted">Import aktualizuje obrat, u existujících obcí nemění stav.</span>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= rep_zavoz_e($csrfToken) ?>">
                <input type="hidden" name="action" value="import_xlsx">

                <div class="col-md-7 col-lg-5">
                    <label for="xlsx_file" class="form-label">XLSX soubor</label>
                    <input type="file" name="xlsx_file" id="xlsx_file" class="form-control" accept=".xlsx,.xls">
                    <div class="form-text">Povinné sloupce: <strong>PSC</strong> a <strong>PRODEJ</strong>.</div>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> importovat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Obce</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($rows), 0, ',', ' ') ?> záznamů</span>
            </div>
            <div class="small text-muted">Vyhledej obec nebo PSČ a změň stav přímo v řádku.</div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-striped table-hover table-bordered table-sm align-middle w-100 js-datatable"
                    id="DataTableRepZavozObce"
                    data-state-key="rep-zavoz-obce-all-v2"
                    data-column-filters="1"
                    data-column-filter-placement="header"
                    data-order='[[ 1, "asc" ]]'
                    data-page-length='500'
                >
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="select-filter">Stav</th>
                        <th class="text-filter dt-autocomplete">Obec</th>
                        <th class="text-filter dt-autocomplete">Okres</th>
                        <th class="text-filter dt-autocomplete">Kraj</th>
                        <th class="no-filter">Prodej</th>
                        <th class="no-filter">Upraveno</th>
                        <th class="text-filter">PSČ</th>
                        <th class="text-filter">PSČ z importu</th>
                        <th class="text-filter dt-autocomplete">Kontakt OZ</th>
                        <th class="no-sort no-filter">Upravit</th>
                    </tr>
                    </thead>

                    <tfoot class="table-light">
                    <tr>
                        <th>Stav</th>
                        <th>Obec</th>
                        <th>Okres</th>
                        <th>Kraj</th>
                        <th>Prodej</th>
                        <th>Upraveno</th>
                        <th>PSČ</th>
                        <th>PSČ z importu</th>
                        <th>Kontakt OZ</th>
                        <th>Upravit</th>
                    </tr>
                    </tfoot>

                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $rowStatus = rep_zavoz_normalize_ui_status((string)($row['status'] ?? 'not_served'));
                        $statusLabel = (string)$statuses[$rowStatus]['label'];
                        $pscList = trim((string)($row['psc_list'] ?? ''));
                        $importedPscList = trim((string)($row['imported_psc_list'] ?? ''));
                        $pscShort = mb_strlen($pscList, 'UTF-8') > 28 ? mb_substr($pscList, 0, 28, 'UTF-8') . '…' : $pscList;
                        $importedPscShort = mb_strlen($importedPscList, 'UTF-8') > 28 ? mb_substr($importedPscList, 0, 28, 'UTF-8') . '…' : $importedPscList;
                        $ozName = trim((string)($row['oz_jmeno'] ?? ''));
                        $ozSource = 'obec';
                        if ($ozName === '' && trim((string)($row['oz_email'] ?? '')) !== '') {
                            $ozName = (string)$row['oz_email'];
                        }
                        if ($ozName === '') {
                            $ozName = trim((string)($row['okres_oz_jmeno'] ?? ''));
                            $ozSource = 'okres';
                            if ($ozName === '' && trim((string)($row['okres_oz_email'] ?? '')) !== '') {
                                $ozName = (string)$row['okres_oz_email'];
                            }
                        }
                        ?>
                        <tr>
                            <td data-search="<?= rep_zavoz_e($statusLabel) ?>" data-filter="<?= rep_zavoz_e($statusLabel) ?>"><?= rep_zavoz_status_badge($rowStatus) ?></td>
                            <td class="fw-semibold"><?= rep_zavoz_e((string)$row['nazev']) ?></td>
                            <td><?= rep_zavoz_e((string)($row['okres'] ?? '')) ?></td>
                            <td><?= rep_zavoz_e((string)($row['kraj'] ?? '')) ?></td>
                            <td class="text-end text-nowrap" data-order="<?= rep_zavoz_e((string)(float)$row['prodej']) ?>"><?= rep_zavoz_e(number_format((float)$row['prodej'], 2, ',', ' ')) ?></td>
                            <td class="text-nowrap">
                                <?php if (!empty($row['ts_u'])): ?>
                                    <?= rep_zavoz_e((string)format_datetime_www((string)$row['ts_u'])) ?>
                                    <br><small class="text-muted"><?= rep_zavoz_e((string)($row['user_u'] ?? '')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap small" data-search="<?= rep_zavoz_e($pscList) ?>" title="<?= rep_zavoz_e($pscList) ?>"><?= rep_zavoz_e($pscShort) ?></td>
                            <td class="text-nowrap small" data-search="<?= rep_zavoz_e($importedPscList) ?>" title="<?= rep_zavoz_e($importedPscList) ?>"><?= rep_zavoz_e($importedPscShort) ?></td>
                            <td>
                                <?= rep_zavoz_e($ozName) ?>
                                <?php if ($ozName !== '' && $ozSource === 'okres'): ?>
                                    <br><small class="text-muted">kontakt z okresu</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    title="Upravit"
                                    aria-label="Upravit"
                                    data-rep-zavoz-status-open
                                    data-obec-id="<?= (int)$row['obec_id'] ?>"
                                    data-status="<?= rep_zavoz_e($rowStatus) ?>"
                                    data-name="<?= rep_zavoz_e((string)$row['nazev']) ?>"
                                    data-meta="<?= rep_zavoz_e(trim(implode(' | ', array_filter([(string)($row['okres'] ?? ''), (string)($row['kraj'] ?? ''), (string)($row['psc_list'] ?? '') !== '' ? 'PSČ ' . (string)$row['psc_list'] : '']))) ) ?>"
                                    data-sales="<?= rep_zavoz_e('Aktuální stav: ' . $statusLabel . ' | Prodej: ' . number_format((float)$row['prodej'], 2, ',', ' ')) ?>"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($activeSubpage === '02'): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary">Mapa obcí</h6>
                <span class="small text-muted">Mapový podklad + hranice aktivních závozových obcí a body všech obcí. Kliknutí na obec otevře změnu stavu.</span>
            </div>
            <div class="btn-group flex-wrap" role="group" aria-label="Filtr mapy">
                <button type="button" class="btn btn-sm btn-outline-dark active" data-rep-zavoz-map-filter="all">Všechny</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-rep-zavoz-map-filter="served">Obsluhujeme</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-rep-zavoz-map-filter="not_served">Neobsluhujeme</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-rep-zavoz-map-filter="excluded">Vyloučené</button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-rep-zavoz-map-filter="review">Ke kontrole</button>
            </div>
        </div>
        <div class="card-body">
            <div class="ratio ratio-16x9">
                <div
                    data-rep-zavoz-map
                    data-edit-modal="#repZavozMapStatusModal"
                    data-points="<?= rep_zavoz_e((string)$mapPointsJson) ?>"
                    data-areas="<?= rep_zavoz_e((string)$mapAreasJson) ?>"
                ></div>
            </div>
            <div class="d-flex flex-wrap gap-3 small text-muted mt-3">
                <span><i class="bi bi-circle-fill text-danger me-1"></i>Obsluhujeme</span>
                <span><i class="bi bi-circle-fill text-secondary me-1"></i>Neobsluhujeme</span>
                <span><i class="bi bi-circle-fill text-primary me-1"></i>Vyloučeno</span>
                <span><i class="bi bi-circle-fill text-warning me-1"></i>Ke kontrole</span>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary">Okresy</h6>
                <span class="small text-muted">Okresní kontakt je fallback pro oblast. Kontakt nastavený přímo u obce má prioritu.</span>
            </div>
            <span class="small text-muted">načteno <?= number_format(count($okresRows), 0, ',', ' ') ?> záznamů</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-striped table-hover table-bordered table-sm align-middle w-100 js-datatable"
                    id="DataTableRepZavozOkresy"
                    data-state-key="rep-zavoz-okresy-v1"
                    data-column-filters="1"
                    data-column-filter-placement="header"
                    data-order='[[ 1, "asc" ], [ 0, "asc" ]]'
                    data-page-length='100'
                >
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="text-filter dt-autocomplete">Okres</th>
                        <th class="text-filter dt-autocomplete">Kraj</th>
                        <th class="no-filter">Obce</th>
                        <th class="no-filter">Obsluhujeme</th>
                        <th class="no-filter">Neobsluhujeme</th>
                        <th class="no-filter">Vyloučené</th>
                        <th class="no-filter">Ke kontrole</th>
                        <th class="no-filter">Prodej</th>
                        <th class="text-filter dt-autocomplete">Kontakt oblast</th>
                        <th class="text-filter">Poznámka</th>
                        <th class="no-filter">Upraveno</th>
                        <th class="no-sort no-filter">Akce</th>
                    </tr>
                    </thead>

                    <tfoot class="table-light">
                    <tr>
                        <th>Okres</th>
                        <th>Kraj</th>
                        <th>Obce</th>
                        <th>Obsluhujeme</th>
                        <th>Neobsluhujeme</th>
                        <th>Vyloučené</th>
                        <th>Ke kontrole</th>
                        <th>Prodej</th>
                        <th>Kontakt oblast</th>
                        <th>Poznámka</th>
                        <th>Upraveno</th>
                        <th>Akce</th>
                    </tr>
                    </tfoot>

                    <tbody>
                    <?php foreach ($okresRows as $row): ?>
                        <?php
                        $selectedOzId = (int)($row['obchodni_zastupce_id'] ?? 0);
                        $okresOzName = trim((string)($row['oz_jmeno'] ?? ''));
                        if ($okresOzName === '' && trim((string)($row['oz_email'] ?? '')) !== '') {
                            $okresOzName = (string)$row['oz_email'];
                        }
                        $okresNote = trim((string)($row['note_internal'] ?? ''));
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= rep_zavoz_e((string)$row['nazev']) ?></td>
                            <td><?= rep_zavoz_e((string)($row['kraj'] ?? '')) ?></td>
                            <td class="text-end" data-order="<?= (int)($row['obce_count'] ?? 0) ?>"><?= number_format((int)($row['obce_count'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="text-end" data-order="<?= (int)($row['served_count'] ?? 0) ?>"><?= number_format((int)($row['served_count'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="text-end" data-order="<?= (int)($row['not_served_count'] ?? 0) ?>"><?= number_format((int)($row['not_served_count'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="text-end" data-order="<?= (int)($row['excluded_count'] ?? 0) ?>"><?= number_format((int)($row['excluded_count'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="text-end" data-order="<?= (int)($row['review_count'] ?? 0) ?>"><?= number_format((int)($row['review_count'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="text-end text-nowrap" data-order="<?= rep_zavoz_e((string)(float)($row['prodej'] ?? 0)) ?>"><?= rep_zavoz_e(number_format((float)($row['prodej'] ?? 0), 2, ',', ' ')) ?></td>
                            <td data-search="<?= rep_zavoz_e($okresOzName) ?>">
                                <?= $okresOzName !== '' ? rep_zavoz_e($okresOzName) : '<span class="text-muted">bez kontaktu</span>' ?>
                            </td>
                            <td><?= $okresNote !== '' ? rep_zavoz_e($okresNote) : '<span class="text-muted">-</span>' ?></td>
                            <td class="text-nowrap">
                                <?php if (!empty($row['ts_u'])): ?>
                                    <?= rep_zavoz_e((string)format_datetime_www((string)$row['ts_u'])) ?>
                                    <br><small class="text-muted"><?= rep_zavoz_e((string)($row['user_u'] ?? '')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    title="Upravit"
                                    aria-label="Upravit"
                                    data-rep-zavoz-okres-open
                                    data-okres-id="<?= (int)$row['id'] ?>"
                                    data-name="<?= rep_zavoz_e((string)$row['nazev']) ?>"
                                    data-meta="<?= rep_zavoz_e((string)($row['kraj'] ?? '')) ?>"
                                    data-obchodni-zastupce-id="<?= $selectedOzId > 0 ? (int)$selectedOzId : '' ?>"
                                    data-note="<?= rep_zavoz_e($okresNote) ?>"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
