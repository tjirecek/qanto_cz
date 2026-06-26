<?php
declare(strict_types=1);

global $pdo;

require_once __DIR__ . '/../cron/cron_log_helper.php';

$result = strtoupper(trim((string)($_GET['result'] ?? '')));
if (!in_array($result, ['', 'SUCCESS', 'ERROR'], true)) {
    $result = '';
}

$stats = [
    'total' => 0,
    'success' => 0,
    'error' => 0,
];

try {
    cronLogEnsureTable($pdo);

    $rowStats = $pdo->query("
        SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN result = 'SUCCESS' THEN 1 ELSE 0 END) AS success_cnt,
            SUM(CASE WHEN result = 'ERROR' THEN 1 ELSE 0 END) AS error_cnt
        FROM log_cron
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['total'] = (int)($rowStats['total_cnt'] ?? 0);
    $stats['success'] = (int)($rowStats['success_cnt'] ?? 0);
    $stats['error'] = (int)($rowStats['error_cnt'] ?? 0);
} catch (Throwable $e) {}

$ajaxUrl = 'functions/ajax/cron_log_dt.php';
if ($result !== '') {
    $ajaxUrl .= '?result=' . rawurlencode($result);
}

$baseUrl = 'index.php?section=02&amp;page=02&amp;sec_page=05';
?>

<style>
    .cron-log-card,
    .cron-log-card .card-body {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
    }

    .cron-log-table-wrap {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .cron-log-table-wrap .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0;
    }

    .cron-log-table-wrap table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        margin: 0 !important;
    }

    .cron-log-table-wrap th,
    .cron-log-table-wrap td {
        max-width: 0;
    }

    .cron-log-table-wrap th:nth-child(1),
    .cron-log-table-wrap td:nth-child(1) { width: 70px; }
    .cron-log-table-wrap th:nth-child(2),
    .cron-log-table-wrap td:nth-child(2) { width: 170px; }
    .cron-log-table-wrap th:nth-child(3),
    .cron-log-table-wrap td:nth-child(3) { width: 200px; }
    .cron-log-table-wrap th:nth-child(4),
    .cron-log-table-wrap td:nth-child(4) { width: 260px; }
    .cron-log-table-wrap th:nth-child(5),
    .cron-log-table-wrap td:nth-child(5) { width: 80px; }
    .cron-log-table-wrap th:nth-child(6),
    .cron-log-table-wrap td:nth-child(6) { width: 100px; }
    .cron-log-table-wrap th:nth-child(7),
    .cron-log-table-wrap td:nth-child(7),
    .cron-log-table-wrap th:nth-child(8),
    .cron-log-table-wrap td:nth-child(8) { width: 150px; }
    .cron-log-table-wrap th:nth-child(9),
    .cron-log-table-wrap td:nth-child(9) { width: 90px; }
    .cron-log-table-wrap th:nth-child(10),
    .cron-log-table-wrap td:nth-child(10) { width: 320px; }

    .cron-log-table-wrap .cron-log-text {
        display: block;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cron-log-message-btn {
        display: inline-block;
        margin-top: 2px;
    }

    .cron-log-message-full {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis cron logu</h1>

    <div class="d-flex flex-wrap gap-2">
        <span class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">
            záznamů: <?= (int)$stats['total'] ?>
        </span>
        <span class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            success: <?= (int)$stats['success'] ?>
        </span>
        <span class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
            error: <?= (int)$stats['error'] ?>
        </span>
    </div>
</div>

<div class="modal fade" id="cronLogMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail zprávy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <div class="modal-body">
                <div class="cron-log-message-full" id="cronLogMessageModalBody"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (event) {
    var trigger = event.target.closest('.cron-log-message-btn');
    if (!trigger) {
        return;
    }

    event.preventDefault();

    var body = document.getElementById('cronLogMessageModalBody');
    var modalEl = document.getElementById('cronLogMessageModal');
    if (!body || !modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    body.textContent = trigger.getAttribute('data-message') || '';
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
});
</script>

<div class="card shadow mb-4 cron-log-card">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Cron log</h6>
            <span class="d-none d-sm-inline-block ms-2 text-muted">server-side DataTables</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $baseUrl ?>"
               class="btn btn-sm <?= $result === '' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">
                vše
            </a>
            <a href="<?= $baseUrl ?>&amp;result=SUCCESS"
               class="btn btn-sm <?= $result === 'SUCCESS' ? 'btn-success' : 'btn-outline-success' ?> shadow-sm">
                success
            </a>
            <a href="<?= $baseUrl ?>&amp;result=ERROR"
               class="btn btn-sm <?= $result === 'ERROR' ? 'btn-danger' : 'btn-outline-danger' ?> shadow-sm">
                error
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="cron-log-table-wrap">
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
                    <th class="text-filter autocomplete">Cron</th>
                    <th class="text-filter autocomplete">Script</th>
                    <th class="text-filter autocomplete">Zdroj</th>
                    <th class="no-filter">Počet</th>
                    <th class="no-filter">Výsledek</th>
                    <th class="text-filter autocomplete">Start</th>
                    <th class="text-filter autocomplete">Konec</th>
                    <th class="no-filter">Trvání</th>
                    <th class="text-filter autocomplete">Message</th>
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
