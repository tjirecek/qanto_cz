<?php
declare(strict_types=1);

include "functions/fun_system.php";

// --- GET parametry (bez mysqli) ---
$limit = isset($_GET['limit'])
        ? (int)$_GET['limit']
        : (int)(sp_hodnota('limit_users-vypis') ?? 500);

// fallback: kdyby bylo v DB 0, tak stejně chceme 500
if ($limit <= 0) {
    $limit = 500;
}

$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$show  = isset($_GET['show'])  ? (int)$_GET['show']  : 0;

// --- počty ---
$count = (int)users_count($valid);
if ($count <= $limit) {
    $limit = $count;
}

// --- mazání (pokud přišlo) ---
if (isset($_GET['del'])) {
    users_delete((int)$_GET['del']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_password_reset_user_id'])) {
    users_password_reset_send((int)$_POST['send_password_reset_user_id']);
    _redirect_self();
}
?>

<style>
    .users-vypis-name-col {
        min-width: 240px;
        white-space: nowrap;
    }
</style>

<!-- Users Heading -->
<?php _admin_flash_render(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Výpis uživatelských účtů</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;show=1"
           class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            přidat uživatelský účet <i class="bi bi-plus-circle"></i>
        </a>

        <?php if (admin_session_prava() === 1): ?>
            <a href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;limit=9999&amp;valid=0"
               class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                zobrazit nevalidní záznamy <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>

        <a href="#" class="btn btn-sm btn-primary shadow-sm d-none d-sm-inline-block">
            <i class="bi bi-download"></i>
        </a>
    </div>
</div>

<!-- Users add -->
<?php if ($show === 1 || $show === 11): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary d-sm-inline">Přidání uživatelského účtu</h6>
        </div>

        <?php
        if ($show === 11) {
            echo '<div class="btn btn-success btn-icon-split w-25 text-left">
                    <span class="icon text-white-50"><i class="bi bi-check2-circle"></i></span>
                    <span class="text">Uživatelský účet byl vložen</span>
                  </div>';
        }
        include "inc/settings/users_add.php";
        ?>
    </div>
<?php endif; ?>

<!-- Users edit -->
<?php if ($show === 2 || $show === 21): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success d-sm-inline">Editace uživatelského účtu</h6>
        </div>

        <?php
        if ($show === 21) {
            echo '<div class="btn btn-success btn-icon-split w-25 text-left">
                    <span class="icon text-white-50"><i class="bi bi-check2-circle"></i></span>
                    <span class="text">Uživatelský účet byl uložen</span>
                  </div>';
        } else {
            include "inc/settings/users_edit.php";
        }
        ?>
    </div>
<?php endif; ?>

<!-- DataTables -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary d-sm-inline">Uživatelské účty</h6>
        <span class="d-none d-sm-inline-block">načteno <?php echo (int)$limit; ?> záznamů</span>

        <?php if ((int)(sp_hodnota('limit_users-vypis') ?? 500) <= $count): ?>
            <a href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;limit=0"
               class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                načíst všechny záznamy (<?php echo (int)$count; ?>)
                <i class="bi bi-arrow-repeat"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                    class="table table-striped table-hover table-bordered table-sm js-datatable"
                    data-order='[[ 0, "desc" ]]'
                    data-page-length='500'
                    data-state-key="secure-users-vypis"
                    data-state-keep-filters="1"
                    id="DataTable"
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th>ID</th>
                    <th class="no-sort text-filter autocomplete users-vypis-name-col">Jméno</th>
                    <th>Login</th>
                    <th class="text-filter autocomplete">E-mail</th>
                    <th class="no-sort no-filter">Reset hesla</th>
                    <th class="no-sort no-filter">Upravit</th>
                    <th class="no-sort no-filter">Smazat</th>
                    <th>Aktivní</th>
                    <th>Admin</th>
                    <th>Skupina</th>
                    <th>Poslední login admin</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>ID</th>
                    <th class="users-vypis-name-col">Jméno</th>
                    <th>Login</th>
                    <th>E-mail</th>
                    <th>Reset hesla</th>
                    <th>Upravit</th>
                    <th>Smazat</th>
                    <th>Aktivní</th>
                    <th>Admin</th>
                    <th>Skupina</th>
                    <th>Poslední login admin</th>
                </tr>
                </tfoot>

                <tbody>
                <?php users_vypis($limit, $valid); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
