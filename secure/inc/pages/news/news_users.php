<?php
declare(strict_types=1);

include "functions/fun_news.php";

// žádné mysqli – jen čisté typování
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_news-users');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)news_users_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}

// akce (všechno jako int)
$del   = isset($_GET['del'])   ? (int)$_GET['del']   : 0;
$end   = isset($_GET['end'])   ? (int)$_GET['end']   : 0;
$renew = isset($_GET['renew']) ? (int)$_GET['renew'] : 0;
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis uživatelů novinek</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat uživatele novinek
            <i class="bi bi-plus-circle ms-1"></i>
        </a>

        <?php if ((admin_session_prava()) == 1): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní záznamy
                <i class="bi bi-circle-half ms-1"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Page actions -->
<?php
if ($del > 0)   { news_users_delete($del); }
if ($end > 0)   { news_users_end($end); }
if ($renew > 0) { news_users_renew($renew); }
?>

<!-- Page add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přidání uživatele novinek</h6>
        </div>

        <div class="card-body">
            <?php
            if ($show === 11) {
                echo '<div class="alert alert-success d-inline-flex align-items-center mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <div>Uživatel novinek byl vložen</div>
                      </div>';
            }
            include "inc/pages/news/news_users_add.php";
            ?>
        </div>
    </div>
<?php endif; ?>

<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Uživatelé novinek</h6>
        <span class="d-none d-sm-inline-block ms-2">načteno <?php echo (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_news-users') <= $count): ?>
            <a href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block ms-2">
                načíst všechny záznamy (<?php echo (int)$count; ?>)
                <i class="bi bi-arrow-repeat ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table data-order='[[ 2, "asc" ]]'
                   data-page-length='100'
                   class="table table-striped table-hover table-bordered"
                   id="dataTable" width="100%" cellspacing="0">

                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>E-mail</th>
                    <th>Datum od</th>
                    <th>Datum do</th>
                    <th>Registrován</th>
                    <th>Ukončit</th>
                    <th>Obnovit</th>
                    <th>Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>E-mail</th>
                    <th>Datum od</th>
                    <th>Datum do</th>
                    <th>Registrován</th>
                    <th>Ukončit</th>
                    <th>Obnovit</th>
                    <th>Smazat</th>
                </tr>
                </tfoot>

                <tbody>
                <?php news_users_vypis($limit, $valid); ?>
                </tbody>

            </table>
        </div>
    </div>
</div>