<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_ples.php';

global $pdo;

$error = '';
$notice = '';
$yearRows = [];
$selectedYears = rep_ples_parse_years($_GET['years'] ?? []);
$showAllYears = isset($_GET['all']) && (string)$_GET['all'] === '1';
$filterSubmitted = isset($_GET['filter']) && (string)$_GET['filter'] === '1';
$showNoYears = (isset($_GET['none']) && (string)$_GET['none'] === '1') || ($filterSubmitted && !$showAllYears && $selectedYears === []);
$valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
$rows = [];
$csrfToken = (string)admin_session_get('rep_ples_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_ples_csrf_token', $csrfToken);
}

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }

        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění upravovat registrace.');
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'invalidate' || $action === 'validate') {
            $newValid = $action === 'validate' ? 1 : 0;
            rep_ples_set_valid($pdo, (int)($_POST['id'] ?? 0), $newValid);
            $notice = $newValid === 1 ? 'Registrace byla obnovena.' : 'Registrace byla znevalidněna.';
            $selectedYears = rep_ples_parse_years($_POST['years'] ?? []);
            $showAllYears = isset($_POST['all']) && (string)$_POST['all'] === '1';
            $showNoYears = isset($_POST['none']) && (string)$_POST['none'] === '1';
            $valid = isset($_POST['valid']) && (string)$_POST['valid'] === '0' ? 0 : 1;
        }
    }

    $yearRows = rep_ples_years($pdo);
    if (!$showAllYears && !$showNoYears && !$filterSubmitted && !isset($_GET['years']) && $selectedYears === [] && isset($yearRows[0]['rok'])) {
        $selectedYears = [(int)$yearRows[0]['rok']];
    }
    $rows = $showNoYears ? [] : rep_ples_rows($pdo, $selectedYears, $valid);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$selectedYearMap = array_fill_keys($selectedYears, true);
$exportUrl = rep_ples_export_url($selectedYears, $showNoYears, $valid);
$validToggleParams = [
    'section' => '02',
    'page' => '05',
    'sec_page' => '01',
    'valid' => $valid === 1 ? '0' : '1',
];
if ($showAllYears) {
    $validToggleParams['all'] = '1';
} elseif ($showNoYears) {
    $validToggleParams['none'] = '1';
} else {
    $validToggleParams['filter'] = '1';
    foreach ($selectedYears as $selectedYear) {
        $validToggleParams['years'][] = (string)$selectedYear;
    }
}
$validToggleUrl = 'index.php?' . http_build_query($validToggleParams, '', '&');
$validCount = ($pdo instanceof PDO) ? rep_ples_count($pdo, 1) : 0;
$invalidCount = ($pdo instanceof PDO) ? rep_ples_count($pdo, 0) : 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Ples</h1>
        <div class="text-muted small">Project agenda qanto.cz: registrace hostů na ples z frontend formuláře.</div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <?php if ($valid === 1): ?>
            <a href="<?= rep_ples_e($validToggleUrl) ?>" class="btn btn-sm btn-danger shadow-sm">zobrazit nevalidní <i class="bi bi-slash-circle ms-1"></i></a>
        <?php else: ?>
            <a href="<?= rep_ples_e($validToggleUrl) ?>" class="btn btn-sm btn-outline-primary shadow-sm">zobrazit validní <i class="bi bi-arrow-repeat ms-1"></i></a>
        <?php endif; ?>
        <span class="btn btn-sm btn-light shadow-sm">načteno: <?= number_format(count($rows), 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-success shadow-sm">validní: <?= number_format($validCount, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-secondary shadow-sm">nevalidní: <?= number_format($invalidCount, 0, ',', ' ') ?></span>
        <a class="btn btn-sm btn-success shadow-sm" href="<?= rep_ples_e($exportUrl) ?>">
            <i class="bi bi-file-earmark-excel me-1"></i> Export XLSX
        </a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <?= rep_ples_e($error) ?>
    </div>
<?php else: ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <div><?= rep_ples_e($notice) ?></div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div class="flex-grow-1">
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Registrace</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($rows), 0, ',', ' ') ?> záznamů</span>
                <form method="get" class="d-flex flex-wrap gap-2 align-items-center mt-2" data-rep-ples-filter>
                    <input type="hidden" name="section" value="02">
                    <input type="hidden" name="page" value="05">
                    <input type="hidden" name="sec_page" value="01">
                    <input type="hidden" name="filter" value="1">
                    <input type="hidden" name="valid" value="<?= (int)$valid ?>">
                    <?php foreach ($yearRows as $yearRow): ?>
                        <?php
                        $year = (int)$yearRow['rok'];
                        $checked = !$showNoYears && ($showAllYears || isset($selectedYearMap[$year]));
                        ?>
                        <input type="checkbox" class="btn-check" name="years[]" value="<?= $year ?>" id="plesYear<?= $year ?>" <?= $checked ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary btn-sm" for="plesYear<?= $year ?>"><?= $year ?><span class="badge text-bg-light ms-1"><?= (int)$yearRow['total'] ?></span></label>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filtrovat</button>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=05&amp;sec_page=01&amp;all=1&amp;valid=<?= (int)$valid ?>">Všechny</a>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?section=02&amp;page=05&amp;sec_page=01&amp;none=1&amp;valid=<?= (int)$valid ?>">Žádné</a>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "desc" ], [ 2, "desc" ]]' data-page-length="500">
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="no-filter">ID</th>
                        <th class="select-filter">Rok</th>
                        <th class="text-filter">Datum</th>
                        <th class="text-filter dt-autocomplete">Jméno</th>
                        <th class="text-filter dt-autocomplete">Firma</th>
                        <th class="text-filter dt-autocomplete">Město</th>
                        <th class="text-filter">PSČ</th>
                        <th class="select-filter">Osob</th>
                        <th class="select-filter">Ubytování</th>
                        <th class="text-filter">E-mail</th>
                        <th class="text-filter">Mobil</th>
                        <th class="text-filter dt-autocomplete">Pozval</th>
                        <th class="text-filter">Poznámka</th>
                        <th class="text-filter">Upraveno</th>
                        <th class="select-filter">Valid</th>
                        <th class="no-sort no-filter">Akce</th>
                    </tr>
                    </thead>
                    <tfoot class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Rok</th>
                        <th>Datum</th>
                        <th>Jméno</th>
                        <th>Firma</th>
                        <th>Město</th>
                        <th>PSČ</th>
                        <th>Osob</th>
                        <th>Ubytování</th>
                        <th>E-mail</th>
                        <th>Mobil</th>
                        <th>Pozval</th>
                        <th>Poznámka</th>
                        <th>Upraveno</th>
                        <th>Valid</th>
                        <th>Akce</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $fullName = trim((string)($row['name'] ?? '') . ' ' . (string)($row['surname'] ?? '')); ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= (int)$row['rok'] ?></td>
                            <td class="text-nowrap" data-order="<?= rep_ples_e($row['datum'] ?? '') ?>"><?= rep_ples_e($row['datum'] ?? '') ?></td>
                            <td class="fw-semibold"><?= rep_ples_e($fullName) ?></td>
                            <td><?= rep_ples_e($row['firma'] ?? '') ?></td>
                            <td>
                                <?= rep_ples_e($row['mesto'] ?? '') ?>
                                <?php if ((string)($row['ulice'] ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= rep_ples_e($row['ulice'] ?? '') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= rep_ples_e($row['psc'] ?? '') ?></td>
                            <td class="text-end"><?= (int)($row['pocet_osob'] ?? 0) ?></td>
                            <td class="text-center"><?= rep_ples_e($row['ubytovani'] ?? '') ?></td>
                            <td><?= rep_ples_e($row['email'] ?? '') ?></td>
                            <td><?= rep_ples_e($row['mobil'] ?? '') ?></td>
                            <td><?= rep_ples_e($row['pozval'] ?? '') ?></td>
                            <td class="text-break"><?= nl2br(rep_ples_e($row['poznamka'] ?? '')) ?></td>
                            <td data-order="<?= rep_ples_e($row['ts_u'] ?? '') ?>"><?= rep_ples_updated_cell($row) ?></td>
                            <td class="text-center" data-search="<?= (int)($row['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?>">
                                <span class="badge text-bg-<?= (int)($row['valid'] ?? 0) === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int)($row['valid'] ?? 0) === 1 ? 'ANO' : 'NE' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($valid === 1): ?>
                                    <form method="post" class="d-inline" data-confirm="Opravdu znevalidnit tuto registraci?">
                                        <input type="hidden" name="csrf_token" value="<?= rep_ples_e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="invalidate">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="valid" value="<?= (int)$valid ?>">
                                        <?php if ($showAllYears): ?>
                                            <input type="hidden" name="all" value="1">
                                        <?php endif; ?>
                                        <?php if ($showNoYears): ?>
                                            <input type="hidden" name="none" value="1">
                                        <?php endif; ?>
                                        <?php foreach ($selectedYears as $selectedYear): ?>
                                            <input type="hidden" name="years[]" value="<?= (int)$selectedYear ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="Smazat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="d-inline" data-confirm="Opravdu obnovit tuto registraci?">
                                        <input type="hidden" name="csrf_token" value="<?= rep_ples_e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="validate">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="valid" value="<?= (int)$valid ?>">
                                        <?php if ($showAllYears): ?>
                                            <input type="hidden" name="all" value="1">
                                        <?php endif; ?>
                                        <?php if ($showNoYears): ?>
                                            <input type="hidden" name="none" value="1">
                                        <?php endif; ?>
                                        <?php foreach ($selectedYears as $selectedYear): ?>
                                            <input type="hidden" name="years[]" value="<?= (int)$selectedYear ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-success btn-sm" title="Obnovit">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
