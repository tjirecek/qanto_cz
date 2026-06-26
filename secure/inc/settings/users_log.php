<?php
declare(strict_types=1);

include "functions/fun_system.php";

// --- GET ---
$defaultLimit = (int)(sp_hodnota('limit_users-log') ?? 0);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;

// --- počty ---
$count = (int)users_log_count();
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis logu přihlášení do administrace</h1>

    <div class="d-flex flex-wrap gap-2">
        <?php if ($defaultLimit <= $count): ?>
            <a href="index.php?section=02&amp;page=01&amp;sec_page=05&amp;limit=0"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                načíst všechny záznamy (<?= (int)$count ?>)
                <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Log přihlášení</h6>
        <span class="d-none d-sm-inline-block">načteno <?= (int)$limit ?> záznamů</span>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable"
                    data-order='[[ 0, "desc" ]]'
                    data-page-length='500'
                    id="DataTable"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th class="text-filter autocomplete">User</th>
                    <th class="text-filter autocomplete">Skupina</th>
                    <th class="text-filter autocomplete">IP</th>
                    <th data-type="date">Date</th>
                    <th class="text-filter autocomplete">Web</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Skupina</th>
                    <th>IP</th>
                    <th>Date</th>
                    <th>Web</th>
                </tr>
                </tfoot>

                <tbody>
                <?php users_log_vypis($limit); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>