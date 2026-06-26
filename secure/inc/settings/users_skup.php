<?php
declare(strict_types=1);

include "functions/fun_system.php";

// --- GET parametry ---
$limit = isset($_GET['limit'])
        ? (int)$_GET['limit']
        : (int)(sp_hodnota('limit_users-skup') ?? 25);

$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;
$del   = isset($_GET['del'])   ? (int)$_GET['del']   : 0;

// --- počty ---
$count = (int)users_skup_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis skupin uživatelů</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=02&amp;page=01&amp;sec_page=03&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat skupinu <i class="bi bi-plus-circle"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <a href="index.php?section=02&amp;page=01&amp;sec_page=03&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Delete -->
<?php if ($del > 0): users_skup_delete($del); endif; ?>

<!-- Add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">Přidání skupiny uživatelů</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 11): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-check-circle"></i>
                    <div>Skupina uživatelů byla vložena</div>
                </div>
            <?php endif; ?>

            <?php include "inc/settings/users_skup_add.php"; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success">Editace skupiny uživatelů</h6>
        </div>

        <div class="card-body">
            <?php if ($show === 21): ?>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-check-circle"></i>
                    <div>Skupina uživatelů byla uložena</div>
                </div>
            <?php else: ?>
                <?php include "inc/settings/users_skup_edit.php"; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Skupiny uživatelů</h6>
        <span class="d-none d-sm-inline-block">načteno <?= (int)$limit ?> záznamů</span>

        <?php if ((int)(sp_hodnota('limit_users-skup') ?? 25) <= $count): ?>
            <a href="index.php?section=02&amp;page=01&amp;sec_page=03&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
                načíst všechny (<?= (int)$count ?>)
                <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable"
                    data-order='[[ 2, "asc" ]]'
                    data-page-length='25'
                    id="DataTable"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th class="text-filter autocomplete">Název</th>
                    <th>Pořadí</th>
                    <th>Práva</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Název</th>
                    <th>Pořadí</th>
                    <th>Práva</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php users_skup_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>