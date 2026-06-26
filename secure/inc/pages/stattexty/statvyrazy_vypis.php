<?php
declare(strict_types=1);

include "functions/fun_stattexty.php";
global $pdo;

// GET parametry
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_statvyrazy-vypis');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)statvyrazy_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Vyrazy Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis statických výrazů</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat statický výraz
            <i class="fas fa-plus-circle fa-sm text-white-50"></i>
        </a>

        <?php if ((admin_session_prava()) == 1): ?>
            <a href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní záznamy
                <i class="fas fa-circle-notch fa-sm text-white-50"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Vyrazy delete -->
<?php
if (isset($_GET['del'])) {
    $del = (int)$_GET['del'];
    if ($del > 0) {
        statvyrazy_delete($del);
    }
}
?>

<!-- Vyrazy add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání statického výrazu</h6>
        </div>

        <?php if ($show === 11): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check me-2"></i> Statický výraz byl vložen
                </div>
            </div>
        <?php endif; ?>

        <?php include "inc/pages/stattexty/statvyrazy_add.php"; ?>
    </div>
<?php endif; ?>

<!-- Výrazy edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace statického výrazu</h6>
        </div>

        <?php if ($show === 21): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check me-2"></i> Statický výraz byl uložen
                </div>
            </div>
        <?php else: ?>
            <?php include "inc/pages/stattexty/statvyrazy_edit.php"; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- DataTables Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Statické výrazy</h6>
        <span class="d-none d-sm-inline-block">načteno <?php echo (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_statvyrazy-vypis') <= $count): ?>
            <a href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
                načíst všechny záznamy (<?php echo (int)$count; ?>)
                <i class="fas fa-circle-notch fa-sm text-white-50"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table data-order='[[ 1, "asc" ]]'
                   data-page-length='100'
                   class="table table-striped table-hover table-bordered"
                   id="dataTable" width="100%" cellspacing="0">

                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Kód</th>
                    <th>CZ</th>
                    <th>EN</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Kód</th>
                    <th>CZ</th>
                    <th>EN</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php statvyrazy_vypis($limit, $valid); ?>
                </tbody>

            </table>
        </div>
    </div>
</div>
