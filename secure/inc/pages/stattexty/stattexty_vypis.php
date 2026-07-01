<?php
declare(strict_types=1);

include "functions/fun_stattexty.php";
global $pdo;

// GET parametry bezpečně (bez mysqli_real_escape_string)
$defaultLimit = (int)sp_hodnota('limit_stattexty-vypis');
if ($defaultLimit <= 0) {
    $defaultLimit = 500;
}
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)stattexty_count($valid);
$countAll = (int)stattexty_count();
$countValid = (int)stattexty_count(1);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
$loadedCount = max(0, $limit);
$showInactiveUrl = 'index.php?section=01&amp;page=02&amp;sec_page=02&amp;limit=9999&amp;valid=0';
$showActiveUrl = 'index.php?section=01&amp;page=02&amp;sec_page=02&amp;limit=' . (int)$defaultLimit . '&amp;valid=1';
$showAllUrl = 'index.php?section=01&amp;page=02&amp;sec_page=02&amp;limit=0&amp;valid=' . (int)$valid;
?>

<!-- Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis statických textů</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat statický text <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if (admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $showInactiveUrl ?>" class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                    zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
                </a>
            <?php else: ?>
                <a href="<?= $showActiveUrl ?>" class="btn btn-sm btn-outline-primary shadow-sm d-none d-sm-inline-block">
                    zobrazit validní záznamy <i class="bi bi-arrow-repeat ms-1"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($countAll, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($countValid, 0, ',', ' ') ?></span>
    </div>
</div>

<!-- Delete -->
<?php
if (isset($_GET['del'])) {
    stattexty_delete((int)$_GET['del']);
}
?>

<!-- Add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání statického textu</h6>
        </div>

        <?php
        if ($show === 11) {
            echo '<div class="p-3">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check me-2"></i>Statický text byl vložen
                    </div>
                  </div>';
        }
        include "inc/pages/stattexty/stattexty_add.php";
        ?>
    </div>
<?php endif; ?>

<!-- Edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace statického textu</h6>
        </div>

        <?php
        if ($show === 21) {
            echo '<div class="p-3">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check me-2"></i>Statický text byl uložen
                    </div>
                  </div>';
        } else {
            include "inc/pages/stattexty/stattexty_edit.php";
        }
        ?>
    </div>
<?php endif; ?>

<!-- Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Statické texty</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($loadedCount, 0, ',', ' '); ?> záznamů</span>
            <span class="d-none d-sm-inline-block ms-2 text-muted">tabulka `stat_texty`</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?php if ($count > $loadedCount): ?>
                <a href="<?= $showAllUrl ?>" class="btn btn-sm btn-outline-secondary">
                    načíst všechny záznamy (<?= number_format($count, 0, ',', ' '); ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    data-order='[[ 2, "asc" ], [ 0, "desc" ]]'
                    data-page-length='500'
                    class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100"
                    id="DataTable">

                <thead class="table-dark align-middle">
                <tr>
                    <th class="no-filter">ID</th>
                    <th class="text-filter dt-autocomplete">Název statického textu</th>
                    <th class="text-filter dt-autocomplete">Kód</th>
                    <th class="no-filter">Col</th>
                    <th class="no-filter">Galerie</th>
                    <th class="no-filter">Valid</th>
                    <th class="text-filter dt-autocomplete">Upraveno</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Název statického textu</th>
                    <th>Kód</th>
                    <th>Col</th>
                    <th>Galerie</th>
                    <th>Valid</th>
                    <th>Upraveno</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php stattexty_vypis($limit, $valid); ?>
                </tbody>

            </table>
        </div>
    </div>
</div>
