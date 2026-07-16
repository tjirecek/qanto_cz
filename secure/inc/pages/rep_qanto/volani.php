<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_volani.php';

global $pdo;

$error = '';
$notice = '';
$importResults = [];
$period = trim((string)($_GET['obdobi'] ?? ''));
$email = trim((string)($_GET['email'] ?? ''));
$mobil = trim((string)($_GET['mobil'] ?? ''));
$rows = [];
$periodRows = [];
$counts = ['preuctovani' => 0, 'souhrn' => 0, 'detail' => 0];
$csrfToken = (string)admin_session_get('rep_volani_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_volani_csrf_token', $csrfToken);
}

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění importovat vyúčtování.');
        }

        $pdo->beginTransaction();
        try {
            foreach (rep_volani_uploaded_files('prehled_xml') as $file) {
                rep_volani_assert_upload($file);
                $importResults[] = 'Přehled ' . (string)$file['name'] . ': ' . rep_volani_import_prehled($pdo, (string)$file['tmp_name'], (string)$file['name']) . ' řádků.';
            }
            foreach (rep_volani_uploaded_files('souhrn_xml') as $file) {
                rep_volani_assert_upload($file);
                $importResults[] = 'Souhrn ' . (string)$file['name'] . ': ' . rep_volani_import_souhrn($pdo, (string)$file['tmp_name'], (string)$file['name']) . ' řádků.';
            }
            foreach (rep_volani_uploaded_files('detail_xml') as $file) {
                rep_volani_assert_upload($file);
                $importResults[] = 'Detail ' . (string)$file['name'] . ': ' . rep_volani_import_detail($pdo, (string)$file['tmp_name'], (string)$file['name']) . ' řádků.';
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if ($importResults === []) {
            $notice = 'Nebyl nahrán žádný XML soubor.';
        } else {
            $notice = 'Import dokončen.';
        }
    }

    $periodRows = rep_volani_periods($pdo);
    if ($period === '' && isset($periodRows[0]['obdobi'])) {
        $period = (string)$periodRows[0]['obdobi'];
    }
    $counts = rep_volani_counts($pdo);
    $rows = rep_volani_rows($pdo, $period, $email, $mobil);
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Volání</h1>
        <div class="text-muted small">Project agenda qanto.cz: přeúčtování telefonů s historií podle období a telefonního čísla.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-sm-0">
        <span class="btn btn-sm btn-light shadow-sm">přeúčtování: <?= number_format($counts['preuctovani'], 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">souhrn: <?= number_format($counts['souhrn'], 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-light shadow-sm">detail: <?= number_format($counts['detail'], 0, ',', ' ') ?></span>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= rep_volani_e($error) ?></div>
<?php endif; ?>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success">
        <div><?= rep_volani_e($notice) ?></div>
        <?php foreach ($importResults as $importResult): ?>
            <div class="small"><?= rep_volani_e($importResult) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Import XML</h6>
        <div class="small text-muted mt-1">Import nevyprazdňuje tabulky. Přehled se aktualizuje podle klíče <code>obdobi + mobil</code>, souhrn a detail deduplikují řádky přes hash.</div>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= rep_volani_e($csrfToken) ?>">
            <div class="col-lg-4">
                <label for="prehled_xml" class="form-label">Přehled XML</label>
                <input type="file" name="prehled_xml" id="prehled_xml" class="form-control" accept=".xml,text/xml">
                <div class="form-text">Původně <code>vf_prehled.xml</code>.</div>
            </div>
            <div class="col-lg-4">
                <label for="souhrn_xml" class="form-label">Souhrn XML</label>
                <input type="file" name="souhrn_xml" id="souhrn_xml" class="form-control" accept=".xml,text/xml">
                <div class="form-text">Původně <code>vf_souhrn.xml</code>.</div>
            </div>
            <div class="col-lg-4">
                <label for="detail_xml" class="form-label">Detail XML</label>
                <input type="file" name="detail_xml[]" id="detail_xml" class="form-control" accept=".xml,text/xml" multiple>
                <div class="form-text">Lze nahrát více souborů najednou, původně <code>vf_detail_*.xml</code>.</div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i> Importovat nahrané XML
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="flex-grow-1">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přeúčtování</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($rows), 0, ',', ' ') ?> záznamů</span>
            <form method="get" class="d-flex flex-wrap gap-2 align-items-end mt-2">
                <input type="hidden" name="section" value="02">
                <input type="hidden" name="page" value="06">
                <input type="hidden" name="sec_page" value="01">
                <div>
                    <label for="volani-obdobi" class="form-label small mb-1">Období</label>
                    <select name="obdobi" id="volani-obdobi" class="form-select form-select-sm">
                        <option value="">všechna období</option>
                        <?php foreach ($periodRows as $periodRow): ?>
                            <?php $periodValue = (string)($periodRow['obdobi'] ?? ''); ?>
                            <option value="<?= rep_volani_e($periodValue) ?>" <?= $periodValue === $period ? 'selected' : '' ?>>
                                <?= rep_volani_e(rep_volani_period_label($periodValue)) ?> (<?= (int)($periodRow['total'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="volani-email" class="form-label small mb-1">E-mail</label>
                    <input type="text" name="email" id="volani-email" class="form-control form-control-sm" value="<?= rep_volani_e($email) ?>">
                </div>
                <div>
                    <label for="volani-mobil" class="form-label small mb-1">Telefon</label>
                    <input type="text" name="mobil" id="volani-mobil" class="form-control form-control-sm" value="<?= rep_volani_e($mobil) ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filtrovat</button>
                <a href="index.php?section=02&amp;page=06&amp;sec_page=01" class="btn btn-sm btn-outline-secondary">Zrušit filtr</a>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "desc" ], [ 4, "asc" ]]' data-page-length="500">
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="select-filter">Období</th>
                    <th class="text-filter dt-autocomplete">Jméno</th>
                    <th class="text-filter">E-mail</th>
                    <th class="text-filter">Telefon</th>
                    <th class="no-filter text-end">Základ 0 %</th>
                    <th class="no-filter text-end">Základ 21 %</th>
                    <th class="no-filter text-end">Bez DPH</th>
                    <th class="no-filter text-end">S DPH</th>
                    <th class="text-filter">Unify</th>
                    <th class="no-sort no-filter">Souhrn</th>
                    <th class="no-sort no-filter">Detail</th>
                    <th class="no-sort no-filter">Za e-mail</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Období</th>
                    <th>Jméno</th>
                    <th>E-mail</th>
                    <th>Telefon</th>
                    <th>Základ 0 %</th>
                    <th>Základ 21 %</th>
                    <th>Bez DPH</th>
                    <th>S DPH</th>
                    <th>Unify</th>
                    <th>Souhrn</th>
                    <th>Detail</th>
                    <th>Za e-mail</th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $unify = (string)($row['unify'] ?? ''); ?>
                    <?php $rowEmail = (string)($row['email'] ?? ''); ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= rep_volani_e(rep_volani_period_label((string)($row['obdobi'] ?? ''))) ?></td>
                        <td><?= rep_volani_e($row['jmeno'] ?? '') ?></td>
                        <td><?= rep_volani_e($rowEmail) ?></td>
                        <td><?= rep_volani_e($row['mobil'] ?? '') ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zaklad0'] ?? 0) ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zaklad21'] ?? 0) ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zakladcelkem'] ?? 0) ?></td>
                        <td class="text-end fw-semibold"><?= rep_volani_money($row['celkem'] ?? 0) ?></td>
                        <td><code><?= rep_volani_e($unify) ?></code></td>
                        <td class="text-center">
                            <a class="btn btn-success btn-circle btn-sm" href="<?= rep_volani_e(rep_volani_public_url($unify, 1)) ?>" target="_blank" rel="noopener" title="Souhrn">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-success btn-circle btn-sm" href="<?= rep_volani_e(rep_volani_public_url($unify, 2)) ?>" target="_blank" rel="noopener" title="Detail">
                                <i class="bi bi-list-ul"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($rowEmail !== ''): ?>
                                <a class="btn btn-success btn-circle btn-sm" href="<?= rep_volani_e(rep_volani_public_email_url($rowEmail)) ?>" target="_blank" rel="noopener" title="Přehled za e-mail">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
