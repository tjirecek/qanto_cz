<?php
declare(strict_types=1);

require_once __DIR__ . '/../functions/bootstrap.php';
require_once __DIR__ . '/../config.php';

header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_default.php';
require_once SEC_DIR . '/functions/pages_include.php';

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Shared administrace Qanto">
    <meta name="author" content="Qanto admin">
    <meta name="generator" content="TM">
    <title>Administrace | qanto.cz shared</title>
    <link rel="icon" href="/img/design/logo_qanto.webp" sizes="192x192" />

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link href="<?= asset_version(BASE_URL . 'assets/lib/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet" />
    <link href="<?= asset_version('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css'); ?>" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.3.5/b-3.2.5/datatables.min.css">
    <link href="<?= asset_version(BASE_URL . 'assets/css/secure.css'); ?>" rel="stylesheet" type="text/css">

    <script src="<?= asset_version(BASE_URL . 'assets/lib/tinymce/tinymce.min.js') ?>" referrerpolicy="origin"></script>
</head>

<body class="bg-light">
<?php
require_once SEC_DIR . '/functions/admin_login.php';
$uName = admin_session_user_name();
?>

<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="/img/design/logo_qanto.webp" alt="Qanto admin" class="admin-navbar-logo" style="height:32px">
            <span class="d-none d-md-inline">shared administrace qanto.cz</span>
        </a>

        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-lg-flex align-items-center ms-auto gap-3">
            <span class="text-white-50 small">
                Přihlášen: <strong class="text-white"><?= htmlspecialchars($uName, ENT_QUOTES) ?></strong>
            </span>

            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-bounding-box me-1"></i> Účet
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="index.php?logout=1">
                            <i class="bi bi-box-arrow-right me-2"></i>Odhlásit
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="d-flex">
        <aside class="admin-sidebar d-none d-lg-block bg-white border-end min-vh-100 p-3">
            <div class="small text-muted mb-2">MENU</div>
            <nav class="nav nav-pills flex-column gap-1">
                <?php $MENU_ID_PREFIX = 'desk'; ?>
                <?php include SEC_DIR . '/inc/menu/mm_dashboard.php'; ?>
                <hr class="my-2">
                <?php include SEC_DIR . '/inc/menu/mm_all.php'; ?>
                <hr class="my-2">
                <?php include SEC_DIR . '/inc/menu/mm_system.php'; ?>
            </nav>

            <div class="mt-4 pt-3 border-top small text-muted">
                Přihlášen:<br>
                <strong><?= htmlspecialchars($uName, ENT_QUOTES) ?></strong>
            </div>
        </aside>

        <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="adminSidebarLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Zavřít"></button>
            </div>
            <div class="offcanvas-body">
                <nav class="nav nav-pills flex-column gap-1">
                    <?php $MENU_ID_PREFIX = 'mob'; ?>
                    <?php include SEC_DIR . '/inc/menu/mm_dashboard.php'; ?>
                    <hr class="my-2">
                    <?php include SEC_DIR . '/inc/menu/mm_all.php'; ?>
                    <hr class="my-2">
                    <?php include SEC_DIR . '/inc/menu/mm_system.php'; ?>
                </nav>

                <div class="mt-4 pt-3 border-top small text-muted">
                    Přihlášen:<br>
                    <strong><?= htmlspecialchars($uName, ENT_QUOTES) ?></strong>
                    <div class="mt-2">
                        <a class="btn btn-outline-secondary btn-sm w-100" href="index.php?logout=1">
                            <i class="bi bi-box-arrow-right me-1"></i> Odhlásit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <main class="admin-content px-4 py-4">
            <div class="bg-white border rounded-3 p-3 p-md-4 shadow-sm">
                <?php
                global $sec_text;

                $sec_text = (string)($sec_text ?? 'dashboard/dashboard_main');
                if (!preg_match('~^[a-z0-9_/.-]+$~i', $sec_text)) {
                    $sec_text = 'dashboard/dashboard_main';
                }

                $incFile = SEC_DIR . '/inc/' . $sec_text . '.php';
                if (is_file($incFile)) {
                    include $incFile;
                } else {
                    echo '<div class="alert alert-warning">Page not found</div>';
                }
                ?>
            </div>

            <footer class="mt-4 small text-muted">
                <strong>&copy; qanto.cz shared admin <?= date('Y'); ?></strong>
            </footer>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?= asset_version(BASE_URL . 'assets/lib/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.3.5/b-3.2.5/datatables.min.js"></script>
<script src="<?= asset_version(BASE_URL . 'assets/js/sec_datatables_cs.js'); ?>"></script>
<script src="<?= asset_version(BASE_URL . 'assets/js/sec_datatables.js'); ?>"></script>
<script src="<?= asset_version(BASE_URL . 'assets/js/sec_tinymce.js'); ?>"></script>

</body>
</html>
