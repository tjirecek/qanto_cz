<?php
declare(strict_types=1);

require_once "functions/fun_system.php"; // PDO funkce (settings_* / sp_hodnota / ...)

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_settings-vypis');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;
$del   = isset($_GET['del'])   ? (int)$_GET['del']   : 0;

$count = (int)settings_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis systémových proměnných</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=02&amp;page=02&amp;sec_page=02&amp;show=1"
           class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            přidat systémovou proměnnou <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=02&amp;limit=9999&amp;valid=0"
               class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" title="Export">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Page delete -->
<?php if ($del > 0): settings_delete($del); endif; ?>

<!-- Page add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání systémové proměnné</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 11): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <div>Hodnota byla vložena</div>
                </div>
            <?php endif; ?>

            <?php include "inc/settings/system_add.php"; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Page edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace systémové proměnné</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 21): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <div>Hodnota byla uložena</div>
                </div>
            <?php else: ?>
                <?php include "inc/settings/system_edit.php"; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Systémové proměnné</h6>
        <span class="d-none d-sm-inline-block ms-2">načteno <?= (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_settings-vypis') <= $count): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=02&amp;limit=0"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                načíst všechny záznamy (<?= (int)$count; ?>) <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable align-middle"
                    data-order='[[ 1, "asc" ], [ 2, "asc" ]]'
                    data-page-length="25"
                    id="DataTable"
                    width="100%"
                    cellspacing="0"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th>Typ</th>
                    <th class="text-filter autocomplete">Name</th>
                    <th class="text-filter autocomplete">Popis</th>
                    <th>Hodnota</th>
                    <th class="text-filter autocomplete">Textová hodnota</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Typ</th>
                    <th>Name</th>
                    <th>Popis</th>
                    <th>Hodnota</th>
                    <th>Textová hodnota</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php settings_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>