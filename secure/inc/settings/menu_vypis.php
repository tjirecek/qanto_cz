<?php
declare(strict_types=1);

include "functions/fun_system.php";

// GET parametry bezpečně jako int
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_menu-vypis');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)menu_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}

// akce
$del = isset($_GET['del']) ? (int)$_GET['del'] : 0;
if ($del > 0) {
    menu_delete($del);
}
?>

<!-- Page Heading -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis menu hlavního webu</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat menu hlavního webu <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block" title="Export">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Page add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání menu hlavního webu</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 11): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <div>Menu bylo vloženo</div>
                </div>
            <?php endif; ?>

            <?php include "inc/settings/menu_add.php"; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Page edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace menu hlavního webu</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 21): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <div>Menu bylo uloženo</div>
                </div>
            <?php else: ?>
                <?php include "inc/settings/menu_edit.php"; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-inline">Menu hlavního webu</h6>
            <span class="ms-2 text-muted">načteno <?php echo (int)$limit; ?> záznamů</span>
        </div>

        <?php if ((int)sp_hodnota('limit_menu-vypis') <= $count): ?>
            <a href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
                načíst všechny záznamy (<?php echo (int)$count; ?>) <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered table-sm js-datatable"
                   data-order='[[1,"asc"],[3,"asc"]]'
                   data-page-length='500'
                   id="DataTable" width="100%" cellspacing="0">
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th>Menu</th>
                    <th>URL</th>
                    <th>Název</th>
                    <th class="text-center no-sort no-filter">Upravit</th>
                    <th class="text-center no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Menu</th>
                    <th>URL</th>
                    <th>Název</th>
                    <th class="text-center">Upravit</th>
                    <th class="text-center">Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php menu_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>