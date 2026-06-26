<?php
declare(strict_types=1);

include "functions/fun_system.php";

// GET
$limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_menu-vypis');
$valid   = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show    = isset($_GET['show']) ? (int)$_GET['show'] : 0; // zatím nevyužito
$skup_id = isset($_GET['skup_id']) ? (int)$_GET['skup_id'] : 0;

$del = isset($_GET['del']) ? (int)$_GET['del'] : 0;
$add = isset($_GET['add']) ? (int)$_GET['add'] : 0;

$count = (int)menu_users_skup_count($valid);
//if ($limit === 0 || $limit > $count) $limit = $count;

// Actions
if ($del > 0) menu_users_skup_delete($del, $skup_id);
if ($add > 0) menu_users_skup_add($add, $skup_id);
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        Výpis práv skupiny uživatelů <?= htmlspecialchars((string)users_skup_name($skup_id), ENT_QUOTES, 'UTF-8') ?> na menu
    </h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" title="Export">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-inline">Práva uživatelů na menu</h6>
            <span class="ms-2 text-muted">načteno <?= (int)$limit ?> záznamů</span>
        </div>

        <?php if ((int)sp_hodnota('limit_menu-vypis') <= $count): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=04&amp;skup_id=<?= (int)$skup_id ?>&amp;limit=0&amp;valid=<?= (int)$valid ?>"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                načíst všechny záznamy (<?= (int)$count ?>)
                <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable align-middle"
                    data-order='[[1,"asc"],[2,"asc"]]'
                    data-page-length='500'
                    id="DataTable"
                    width="100%"
                    cellspacing="0"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th>Menu</th>
                    <th class="text-filter autocomplete">Název</th>
                    <th class="text-filter autocomplete">URL</th>
                    <th class="no-sort no-filter text-center">Přidat</th>
                    <th class="no-sort no-filter text-center">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Menu</th>
                    <th>Název</th>
                    <th>URL</th>
                    <th class="text-center">Přidat</th>
                    <th class="text-center">Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php menu_users_skup_vypis($skup_id, $limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>