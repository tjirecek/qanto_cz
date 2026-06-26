<?php
global $section, $page, $sec_page, $MENU_ID_PREFIX;

$section  = $section  ?? '';
$page     = $page     ?? '';
$sec_page = $sec_page ?? '';

$adminUserPrava = (string)admin_session_prava();
if (!in_array($adminUserPrava, ['1','2'], true)) {
    return;
}

$isAdmin = ($adminUserPrava === '1');

if (!function_exists('mm_sys_item')) {
    function mm_sys_item(bool $enabled, string $href, string $label, bool $active = false): string
    {
        $cls = 'nav-link py-1' . ($active ? ' active' : '');
        return $enabled
                ? '<a class="'.$cls.'" href="'.$href.'">'.$label.'</a>'
                : '<span class="'.$cls.' text-muted" title="Nemáš oprávnění" style="cursor:not-allowed;">'.$label.'</span>';
    }
}

/** unikátní prefix pro desktop/offcanvas */
$MENU_ID_PREFIX = $MENU_ID_PREFIX ?? 'nav';
$collapseUsersId  = $MENU_ID_PREFIX . '_collapseUsers';
$collapseSystemId = $MENU_ID_PREFIX . '_collapseSystem';

$isUsersOpen  = ($section === "02" && $page === "01");
$isSystemOpen = ($section === "02" && $page === "02");

$usersListActive  = ($section==="02" && $page==="01" && $sec_page==="02" && empty($_GET['show']));
$usersGroupsAct   = ($section==="02" && $page==="01" && $sec_page==="03");
$usersLogActive   = ($section==="02" && $page==="01" && $sec_page==="05");

$varsListActive   = ($section==="02" && $page==="02" && $sec_page==="02" && empty($_GET['show']));
$cronLogActive    = ($section==="02" && $page==="02" && $sec_page==="05");
$changelogActive  = ($section==="02" && $page==="02" && $sec_page==="07");
$migrationsActive = ($section==="02" && $page==="02" && $sec_page==="08");
$emailLogActive   = ($section==="02" && $page==="02" && $sec_page==="09");
?>

<div class="text-uppercase small fw-semibold text-muted mt-3 mb-1">Settings</div>

<!-- Uživatelské účty -->
<a class="nav-link d-flex align-items-center <?= $isUsersOpen ? 'active' : '' ?>"
   href="#<?= $collapseUsersId ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isUsersOpen ? 'true' : 'false' ?>"
   aria-controls="<?= $collapseUsersId ?>">
    <i class="bi bi-people-fill me-2"></i>
    <span>Uživatelské účty</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isUsersOpen ? 'show' : '' ?>" id="<?= $collapseUsersId ?>">
    <div class="nav flex-column ms-4">
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=01&amp;sec_page=02', 'Výpis uživatelů', $usersListActive) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=01&amp;sec_page=03', 'Skupiny uživatelů', $usersGroupsAct) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=01&amp;sec_page=05', 'Log přihlášení', $usersLogActive) ?>
    </div>
</div>

<!-- Systémové proměnné -->
<a class="nav-link d-flex align-items-center <?= $isSystemOpen ? 'active' : '' ?>"
   href="#<?= $collapseSystemId ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isSystemOpen ? 'true' : 'false' ?>"
   aria-controls="<?= $collapseSystemId ?>">
    <i class="bi bi-gear me-2"></i>
    <span>Systémové proměnné</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isSystemOpen ? 'show' : '' ?>" id="<?= $collapseSystemId ?>">
    <div class="nav flex-column ms-4">
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=02&amp;sec_page=02', 'Výpis proměnných', $varsListActive) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=02&amp;sec_page=05', 'Cron Log', $cronLogActive) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=02&amp;sec_page=07', 'ChangeLog', $changelogActive) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=02&amp;sec_page=08', 'DB migrace', $migrationsActive) ?>
        <?= mm_sys_item($isAdmin, 'index.php?section=02&amp;page=02&amp;sec_page=09', 'E-mail log', $emailLogActive) ?>
    </div>
</div>
