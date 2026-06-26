<?php
declare(strict_types=1);

include "functions/fun_news.php";
global $pdo;

// GET parametry (bez mysqli)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_news-vypis');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)news_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- News Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis novinek</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;show=1"
           class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            přidat novinku <i class="bi bi-plus-circle"></i>
        </a>

        <?php if ((admin_session_prava()) == 1): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;limit=9999&amp;valid=0"
               class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                zobrazit nevalidní záznamy <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- News delete -->
<?php
if (isset($_GET['del'])) {
    news_delete((int)$_GET['del']);
}
?>

<!-- News ico delete -->
<?php
if (isset($_GET['icon'])) {
    news_ico_delete((int)$_GET['icon']);
}
?>

<!-- News add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary d-sm-inline">Přidání novinky</h6>
        </div>

        <?php
        if ($show === 11) {
            echo '<div class="btn btn-success btn-icon-split w-25 text-left">
                    <span class="icon text-white-50"><i class="bi bi-check2-circle"></i></span>
                    <span class="text">Novinka byla vložena</span>
                  </div>';
        }
        include "inc/pages/news/news_add.php";
        ?>
    </div>
<?php endif; ?>

<!-- News edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success d-sm-inline">Editace novinky</h6>
        </div>

        <?php
        if ($show === 21) {
            echo '<div class="btn btn-success btn-icon-split w-25 text-left">
                    <span class="icon text-white-50"><i class="bi bi-check2-circle"></i></span>
                    <span class="text">Novinka byla uložena</span>
                  </div>';
        } else {
            include "inc/pages/news/news_edit.php";
        }
        ?>
    </div>
<?php endif; ?>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary d-sm-inline">Novinky</h6>
        <span class="d-none d-sm-inline-block">načteno <?php echo (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_news-vypis') <= $count): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;limit=0"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                načíst všechny záznamy (<?php echo (int)$count; ?>)
                <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered table-sm js-datatable" data-order='[[ 0, "desc" ]]' data-page-length='100' id="DataTable">
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th>Typ</th>
                    <th class="no-filter">Název novinky</th>
                    <th data-type="date">Datum</th>
                    <th>Ikona</th>
                    <th>Galerie</th>
                    <th class="no-sort no-filter">View</th>
                    <th data-type="date" class="no-sort no-filter">Send</th>
                    <th class="no-sort no-filter">Náhled</th>
                    <th class="no-sort no-filter"><?php if (en_on() == 1): ?>EN<?php endif; ?></th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Send</th>
                    <th class="no-sort no-filter">Ikona Del</th>
                    <th class="no-sort no-filter">Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Typ</th>
                    <th>Název novinky</th>
                    <th>Datum</th>
                    <th>Ikona</th>
                    <th>Galerie</th>
                    <th>View</th>
                    <th>Send</th>
                    <th>Náhled</th>
                    <th><?php if (en_on() == 1): ?>EN<?php endif; ?></th>
                    <th>Upravit</th>
                    <th>Send</th>
                    <th>Ikona Del</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php news_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>