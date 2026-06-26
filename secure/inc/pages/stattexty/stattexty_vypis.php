<?php
declare(strict_types=1);

include "functions/fun_stattexty.php";
global $pdo;

// GET parametry bezpečně (bez mysqli_real_escape_string)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)sp_hodnota('limit_stattexty-vypis');
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

$count = (int)stattexty_count($valid);
if ($limit === 0 || $count <= $limit) {
    $limit = $count;
}
?>

<!-- Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis statických textů</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;show=1"
           class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            přidat statický text <i class="fas fa-plus-circle fa-sm text-white-50"></i>
        </a>

        <?php if (admin_session_prava() === 1): ?>
            <a href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;limit=9999&amp;valid=0"
               class="btn btn-sm btn-danger shadow-sm d-none d-sm-inline-block">
                zobrazit nevalidní záznamy <i class="fas fa-circle-notch fa-sm text-white-50"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="fas fa-download fa-sm text-white-50"></i>
        </a>
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
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary d-sm-inline">Statické texty</h6>
        <span class="d-none d-sm-inline-block ms-2">načteno <?= (int)$limit; ?> záznamů</span>

        <?php if ((int)sp_hodnota('limit_stattexty-vypis') <= $count): ?>
            <a href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;limit=0"
               class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block ms-2">
                načíst všechny záznamy (<?= (int)$count; ?>)
                <i class="fas fa-circle-notch fa-sm text-white-50"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table data-order='[[ 0, "desc" ]]'
                   data-page-length='100'
                   class="table table-striped table-hover table-bordered"
                   id="dataTable"
                   width="100%"
                   cellspacing="0">

                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Název statického textu</th>
                    <th>Kód</th>
                    <th>Col</th>
                    <th>Galerie</th>
                    <th><?php if (en_on() == 1): ?>EN<?php endif; ?></th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Název statického textu</th>
                    <th>Kód</th>
                    <th>Col</th>
                    <th>Galerie</th>
                    <th><?php if (en_on() == 1): ?>EN<?php endif; ?></th>
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
