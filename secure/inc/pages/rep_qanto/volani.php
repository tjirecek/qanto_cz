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
$sentFilter = trim((string)($_GET['sent'] ?? ''));
if (!in_array($sentFilter, ['yes', 'no'], true)) {
    $sentFilter = '';
}
$rows = [];
$periodRows = [];
$counts = ['preuctovani' => 0, 'souhrn' => 0, 'detail' => 0];
$csrfToken = (string)admin_session_get('rep_volani_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_volani_csrf_token', $csrfToken);
}

function rep_volani_import_summary_text(string $label, string $fileName, array $summary): string
{
    $periodLabels = [];
    foreach (($summary['periods'] ?? []) as $summaryPeriod => $summaryCount) {
        $periodLabels[] = rep_volani_period_label((string)$summaryPeriod) . ' (' . (int)$summaryCount . ')';
    }

    $parts = [
        $label . ' ' . $fileName . ': importováno ' . number_format((int)($summary['imported'] ?? 0), 0, ',', ' ') . ' řádků',
        'vloženo ' . number_format((int)($summary['inserted'] ?? 0), 0, ',', ' '),
        'aktualizováno ' . number_format((int)($summary['updated'] ?? 0), 0, ',', ' '),
    ];

    if (array_key_exists('skipped_zero', $summary)) {
        $parts[] = 'přeskočeno sdph=0 ' . number_format((int)($summary['skipped_zero'] ?? 0), 0, ',', ' ');
        $parts[] = 'součet sdph ' . rep_volani_money($summary['sum_sdph'] ?? 0);
    } else {
        $parts[] = 'součet s DPH ' . rep_volani_money($summary['sum_s_dph'] ?? 0);
    }

    if ((int)($summary['skipped_missing_period'] ?? 0) > 0) {
        $parts[] = 'přeskočeno bez období ' . number_format((int)$summary['skipped_missing_period'], 0, ',', ' ');
    }
    if ((int)($summary['skipped_missing_mobile'] ?? 0) > 0) {
        $parts[] = 'přeskočeno bez mobilu ' . number_format((int)$summary['skipped_missing_mobile'], 0, ',', ' ');
    }
    if ($periodLabels !== []) {
        $parts[] = 'období ' . implode(', ', $periodLabels);
    }

    return implode(', ', $parts) . '.';
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

        $action = (string)($_POST['action'] ?? 'import_xlsx');
        if ($action === 'send_one_email') {
            $sendId = (int)($_POST['id'] ?? 0);
            $sendResult = rep_volani_send_invoice_email($pdo, $sendId);
            $notice = 'E-mail byl odeslán na ' . (string)($sendResult['recipient'] ?? '') . ' za ' . number_format(count((array)($sendResult['invoices'] ?? [])), 0, ',', ' ') . ' telefonní čísla ve stejném období.';
        } elseif ($action === 'send_unsent_filtered') {
            $period = trim((string)($_POST['filter_period'] ?? $period));
            $email = trim((string)($_POST['filter_email'] ?? $email));
            $mobil = trim((string)($_POST['filter_mobil'] ?? $mobil));
            $sentFilter = 'no';
            $sendResult = rep_volani_send_unsent_filtered($pdo, $period, $email, $mobil);
            $notice = 'Odeslání neodeslaných dokončeno: ' . (int)($sendResult['sent_groups'] ?? $sendResult['sent']) . ' e-mailů úspěšně pro ' . (int)($sendResult['sent_rows'] ?? 0) . ' řádků, ' . (int)$sendResult['failed'] . ' chyb.';
            foreach (array_slice((array)($sendResult['errors'] ?? []), 0, 20) as $sendError) {
                $importResults[] = (string)$sendError;
            }
        } elseif ($action === 'delete_period') {
            $deletePeriod = trim((string)($_POST['delete_period'] ?? ''));
            $deleted = rep_volani_delete_period($pdo, $deletePeriod);
            $notice = 'Období ' . rep_volani_period_label($deletePeriod) . ' bylo smazáno.';
            $importResults[] = 'Smazáno: přeúčtování ' . number_format((int)$deleted['preuctovani'], 0, ',', ' ') . ', souhrn ' . number_format((int)$deleted['souhrn'], 0, ',', ' ') . ', detail ' . number_format((int)$deleted['detail'], 0, ',', ' ') . '.';
            $period = '';
            $email = '';
            $mobil = '';
            $sentFilter = '';
        } else {
            $pdo->beginTransaction();
            try {
                $file = rep_volani_uploaded_file('volani_prehled_xlsx');
                if ($file !== null) {
                    rep_volani_assert_xlsx_upload($file);
                    $summary = rep_volani_import_prehled_xlsx($pdo, (string)$file['tmp_name'], (string)$file['name']);
                    $importResults[] = rep_volani_import_summary_text('Přehled', (string)$file['name'], $summary);
                }
                $file = rep_volani_uploaded_file('volani_souhrn_xlsx');
                if ($file !== null) {
                    rep_volani_assert_xlsx_upload($file);
                    $summary = rep_volani_import_souhrn_xlsx($pdo, (string)$file['tmp_name'], (string)$file['name']);
                    $importResults[] = rep_volani_import_summary_text('Souhrn', (string)$file['name'], $summary);
                }
                $file = rep_volani_uploaded_file('volani_detail_xlsx');
                if ($file !== null) {
                    rep_volani_assert_xlsx_upload($file);
                    $summary = rep_volani_import_detail_xlsx($pdo, (string)$file['tmp_name'], (string)$file['name']);
                    $importResults[] = rep_volani_import_summary_text('Detail', (string)$file['name'], $summary);
                }

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            if ($importResults === []) {
                $notice = 'Nebyl nahrán žádný XLSX soubor.';
            } else {
                $notice = 'Import dokončen.';
            }
        }
    }

    $periodRows = rep_volani_periods($pdo);
    if ($period === '' && isset($periodRows[0]['obdobi'])) {
        $period = (string)$periodRows[0]['obdobi'];
    }
    $counts = rep_volani_counts($pdo);
    $rows = rep_volani_rows($pdo, $period, $email, $mobil, $sentFilter);
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

<div class="card shadow mb-4" data-rep-volani>
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Import XLSX</h6>
        <div class="small text-muted mt-1">
            Importuje XLSX soubory Vodafone. Přehled se aktualizuje podle klíče <code>obdobi + mobil</code>; souhrn a detail se deduplikují podle hashe řádku.
            U přehledu se importují jen řádky s nenulovým <code>sdph</code>.
        </div>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= rep_volani_e($csrfToken) ?>">
            <input type="hidden" name="action" value="import_xlsx">
            <div class="col-lg-6">
                <label for="volani_prehled_xlsx" class="form-label">Přehled XLSX</label>
                <input type="file" name="volani_prehled_xlsx" id="volani_prehled_xlsx" class="form-control" accept=".xlsx">
                <div class="form-text">Sloupce: <code>obdobi</code>, <code>zamestnanec</code>, <code>email</code>, <code>mobil</code>, <code>0dph</code>, <code>21dph</code>, <code>bdph</code>, <code>sdph</code>.</div>
            </div>
            <div class="col-lg-6">
                <label for="volani_souhrn_xlsx" class="form-label">Souhrn XLSX</label>
                <input type="file" name="volani_souhrn_xlsx" id="volani_souhrn_xlsx" class="form-control" accept=".xlsx">
                <div class="form-text">Sloupce kopírované z Vodafone: <code>Období</code>, <code>Mobil</code>, <code>Produktová řada</code>, <code>Položka</code>, <code>Služba</code>, ceny a objemy.</div>
            </div>
            <div class="col-lg-6">
                <label for="volani_detail_xlsx" class="form-label">Detail XLSX</label>
                <input type="file" name="volani_detail_xlsx" id="volani_detail_xlsx" class="form-control" accept=".xlsx">
                <div class="form-text">Detail může být větší soubor; import zpracovává řádky po blocích.</div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i> Importovat nahrané XLSX
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4" data-rep-volani-list>
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
                <div>
                    <label for="volani-sent" class="form-label small mb-1">Odesláno</label>
                    <select name="sent" id="volani-sent" class="form-select form-select-sm">
                        <option value="" <?= $sentFilter === '' ? 'selected' : '' ?>>vše</option>
                        <option value="yes" <?= $sentFilter === 'yes' ? 'selected' : '' ?>>ANO</option>
                        <option value="no" <?= $sentFilter === 'no' ? 'selected' : '' ?>>NE</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filtrovat</button>
                <a href="index.php?section=02&amp;page=06&amp;sec_page=01" class="btn btn-sm btn-outline-secondary">Zrušit filtr</a>
            </form>
            <form method="post" class="mt-2" data-confirm="Opravdu odeslat e-mail všem neodeslaným adresám v aktuálním filtru? E-mail bude seskupený za všechna telefonní čísla dané adresy ve stejném období.">
                <input type="hidden" name="csrf_token" value="<?= rep_volani_e($csrfToken) ?>">
                <input type="hidden" name="action" value="send_unsent_filtered">
                <input type="hidden" name="filter_period" value="<?= rep_volani_e($period) ?>">
                <input type="hidden" name="filter_email" value="<?= rep_volani_e($email) ?>">
                <input type="hidden" name="filter_mobil" value="<?= rep_volani_e($mobil) ?>">
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="bi bi-send me-1"></i> Odeslat neodeslané e-maily v aktuálním filtru
                </button>
                <div class="small text-muted mt-1">Odesílá se jeden e-mail za kombinaci období + e-mail, uvnitř jsou všechna telefonní čísla.</div>
            </form>
            <?php if ($period !== ''): ?>
                <form method="post" class="mt-2" data-confirm="Opravdu smazat celé období <?= rep_volani_e(rep_volani_period_label($period)) ?>? Smaže se přehled, souhrn, detail i evidence odeslání e-mailů pro toto období.">
                    <input type="hidden" name="csrf_token" value="<?= rep_volani_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_period">
                    <input type="hidden" name="delete_period" value="<?= rep_volani_e($period) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash me-1"></i> Smazat období <?= rep_volani_e(rep_volani_period_label($period)) ?>
                    </button>
                    <div class="small text-muted mt-1">Po smazání lze období znovu naimportovat a hromadně odeslat jako neodeslané.</div>
                </form>
            <?php endif; ?>
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
                    <th class="text-filter rep-volani-email-cell">E-mail</th>
                    <th class="text-filter">Telefon</th>
                    <th class="no-filter text-end">Základ 0 %</th>
                    <th class="no-filter text-end">Základ 21 %</th>
                    <th class="no-filter text-end">Bez DPH</th>
                    <th class="no-filter text-end">S DPH</th>
                    <th class="text-filter rep-volani-token-cell">Unify</th>
                    <th class="select-filter text-center">Odesláno</th>
                    <th class="no-sort no-filter">Souhrn</th>
                    <th class="no-sort no-filter">Detail</th>
                    <th class="no-sort no-filter">Za e-mail</th>
                    <th class="no-sort no-filter">Akce</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Období</th>
                    <th>Jméno</th>
                    <th class="rep-volani-email-cell">E-mail</th>
                    <th>Telefon</th>
                    <th>Základ 0 %</th>
                    <th>Základ 21 %</th>
                    <th>Bez DPH</th>
                    <th>S DPH</th>
                    <th class="rep-volani-token-cell">Unify</th>
                    <th>Odesláno</th>
                    <th>Souhrn</th>
                    <th>Detail</th>
                    <th>Za e-mail</th>
                    <th>Akce</th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $unify = (string)($row['unify'] ?? ''); ?>
                    <?php $rowEmail = (string)($row['email'] ?? ''); ?>
                    <?php $emailSentAt = (string)($row['email_sent_at'] ?? ''); ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= rep_volani_e(rep_volani_period_label((string)($row['obdobi'] ?? ''))) ?></td>
                        <td><?= rep_volani_e($row['jmeno'] ?? '') ?></td>
                        <td class="rep-volani-email-cell"><?= rep_volani_e($rowEmail) ?></td>
                        <td><?= rep_volani_e($row['mobil'] ?? '') ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zaklad0'] ?? 0) ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zaklad21'] ?? 0) ?></td>
                        <td class="text-end"><?= rep_volani_money($row['zakladcelkem'] ?? 0) ?></td>
                        <td class="text-end fw-semibold"><?= rep_volani_money($row['celkem'] ?? 0) ?></td>
                        <td class="rep-volani-token-cell"><code><?= rep_volani_e($unify) ?></code></td>
                        <td class="text-center" data-search="<?= $emailSentAt !== '' ? 'ANO' : 'NE' ?>">
                            <?php if ($emailSentAt !== ''): ?>
                                <span class="badge text-bg-success">ANO</span>
                                <div class="small text-muted"><?= rep_volani_e($emailSentAt) ?></div>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">NE</span>
                                <?php if ((string)($row['email_last_error'] ?? '') !== ''): ?>
                                    <div class="small text-danger" title="<?= rep_volani_e($row['email_last_error']) ?>">chyba</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-primary btn-sm rep-volani-action-btn" href="<?= rep_volani_e(rep_volani_public_url($unify, 1)) ?>" target="_blank" rel="noopener" title="Souhrn">
                                <i class="bi bi-card-list"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-secondary btn-sm rep-volani-action-btn" href="<?= rep_volani_e(rep_volani_public_url($unify, 2)) ?>" target="_blank" rel="noopener" title="Detail">
                                <i class="bi bi-list-ul"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($rowEmail !== ''): ?>
                                <a class="btn btn-info btn-sm rep-volani-action-btn" href="<?= rep_volani_e(rep_volani_public_email_url($rowEmail)) ?>" target="_blank" rel="noopener" title="Přehled za e-mail">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <?php if ($rowEmail !== ''): ?>
                                <form method="post" class="d-inline" data-confirm="Opravdu odeslat e-mail pro tuto adresu a období? V e-mailu budou všechna telefonní čísla této adresy.">
                                    <input type="hidden" name="csrf_token" value="<?= rep_volani_e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="send_one_email">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-warning btn-sm rep-volani-action-btn" title="Odeslat e-mail za adresu a období">
                                        <i class="bi bi-send"></i>
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
