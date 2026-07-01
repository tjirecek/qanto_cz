<?php
declare(strict_types=1);

global $pdo;

require_once ROOT_DIR . '/functions/fun_email_log.php';

$status = strtolower(trim((string)($_GET['status'] ?? '')));
if (!in_array($status, ['', 'queued', 'sent', 'failed', 'skipped'], true)) {
    $status = '';
}

$stats = [
    'total' => 0,
    'queued' => 0,
    'sent' => 0,
    'failed' => 0,
];

try {
    if ($pdo instanceof PDO) {
        email_log_prepare_table($pdo);
        $rowStats = $pdo->query("SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued_cnt,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_cnt,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_cnt
         FROM log_emails")->fetch(PDO::FETCH_ASSOC) ?: [];

        $stats['total'] = (int)($rowStats['total_cnt'] ?? 0);
        $stats['queued'] = (int)($rowStats['queued_cnt'] ?? 0);
        $stats['sent'] = (int)($rowStats['sent_cnt'] ?? 0);
        $stats['failed'] = (int)($rowStats['failed_cnt'] ?? 0);
    }
} catch (Throwable $e) {}

$ajaxUrl = 'functions/ajax/email_log_dt.php';
if ($status !== '') {
    $ajaxUrl .= '?status=' . rawurlencode($status);
}

$baseUrl = 'index.php?section=09&amp;page=02&amp;sec_page=09';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis e-mail logu</h1>

    <div class="d-flex flex-wrap gap-2">
        <span class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">záznamů: <?= (int)$stats['total'] ?></span>
        <span class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">queued: <?= (int)$stats['queued'] ?></span>
        <span class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">sent: <?= (int)$stats['sent'] ?></span>
        <span class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">failed: <?= (int)$stats['failed'] ?></span>
    </div>
</div>

<div class="modal fade" id="emailLogDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail e-mailu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <div class="modal-body">
                <div class="email-log-detail-full" id="emailLogDetailModalBody"></div>
            </div>
        </div>
    </div>
</div>



<div class="card shadow mb-4 email-log-card">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">E-mail log</h6>
            <span class="d-none d-sm-inline-block ms-2 text-muted">server-side DataTables</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $baseUrl ?>" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">vše</a>
            <a href="<?= $baseUrl ?>&amp;status=queued" class="btn btn-sm <?= $status === 'queued' ? 'btn-secondary' : 'btn-outline-secondary' ?> shadow-sm">queued</a>
            <a href="<?= $baseUrl ?>&amp;status=sent" class="btn btn-sm <?= $status === 'sent' ? 'btn-success' : 'btn-outline-success' ?> shadow-sm">sent</a>
            <a href="<?= $baseUrl ?>&amp;status=failed" class="btn btn-sm <?= $status === 'failed' ? 'btn-danger' : 'btn-outline-danger' ?> shadow-sm">failed</a>
        </div>
    </div>

    <div class="card-body">
        <div class="email-log-table-wrap">
            <table
                class="table table-striped table-hover table-bordered table-sm js-datatable align-middle"
                id="DataTable"
                data-order='[[ 0, "desc" ]]'
                data-page-length='500'
                data-server-side="1"
                data-ajax="<?= htmlspecialchars($ajaxUrl, ENT_QUOTES) ?>"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="text-filter dt-autocomplete">Kontext</th>
                    <th class="text-filter dt-autocomplete">Šablona</th>
                    <th class="text-filter dt-autocomplete">Příjemce</th>
                    <th class="text-filter dt-autocomplete">Předmět</th>
                    <th class="no-filter">Stav</th>
                    <th class="text-filter dt-autocomplete">Vazba</th>
                    <th class="text-filter dt-autocomplete">Zařazeno</th>
                    <th class="text-filter dt-autocomplete">Odesláno</th>
                    <th class="text-filter dt-autocomplete">Provider / chyba</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th></th><th></th><th></th><th></th><th></th>
                    <th></th><th></th><th></th><th></th><th></th>
                </tr>
                </tfoot>

                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
