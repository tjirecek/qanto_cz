<?php
declare(strict_types=1);

include "functions/fun_news.php";
global $pdo;

// parametry
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_news-typ');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

// počty
$count = (int)news_typ_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis typů novinek</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=01&amp;sec_page=03&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat typ novinek <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if ((int)admin_session_prava() === 1): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=03&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Page delete -->
<?php
if (isset($_GET['del'])) {
    news_typ_delete((int)$_GET['del']);
}
?>

<!-- Page add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání typu novinek</h6>
        </div>

        <?php if ($show === 11): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i> Typ novinek byl vložen
                </div>
            </div>
        <?php endif; ?>

        <?php include "inc/pages/news/news_typ_add.php"; ?>
    </div>
<?php endif; ?>

<!-- Page edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-success d-sm-inline">Editace typu novinek</h6>
        </div>

        <?php if ($show === 21): ?>
            <div class="p-3">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i> Typ novinek byl uložen
                </div>
            </div>
        <?php else: ?>
            <?php include "inc/pages/news/news_typ_edit.php"; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- DataTables Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Typy novinek</h6>
        <span class="d-none d-sm-inline-block ms-2">načteno <?php echo (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_news-typ') <= $count): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=03&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block ms-2">
                načíst všechny záznamy (<?php echo (int)$count; ?>)
                <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table data-order='[[ 2, "asc" ]]' data-page-length='25'
                   class="table table-striped table-hover table-bordered"
                   id="dataTable" width="100%" cellspacing="0">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Název</th>
                    <th>Pořadí</th>
                    <th>Color</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </thead>
                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Název</th>
                    <th>Pořadí</th>
                    <th>Color</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>
                <tbody>
                <?php news_typ_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>