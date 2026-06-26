<?php
global $section, $page, $sec_page, $MENU_ID_PREFIX;

$section  = $section  ?? '';
$page     = $page     ?? '';
$sec_page = $sec_page ?? '';

$adminUserPrava = (string)admin_session_prava();
if (!in_array($adminUserPrava, ['1','2'], true)) {
    return;
}

/** unikátní prefix pro desktop/offcanvas */
$MENU_ID_PREFIX = $MENU_ID_PREFIX ?? 'nav';
$collapseNewsId  = $MENU_ID_PREFIX . '_collapseNews';
$collapseTextyId = $MENU_ID_PREFIX . '_collapseTexty';
$collapseContactsId = $MENU_ID_PREFIX . '_collapseContacts';

$isNewsOpen   = ($section === "01" && $page === "01");
$isStaticOpen = ($section === "01" && $page === "02");
$isContactsOpen = ($section === "01" && $page === "03");

$newsListActive   = ($section==="01" && $page==="01" && $sec_page==="02" && empty($_GET['show']));
$newsAddActive    = ($section==="01" && $page==="01" && $sec_page==="02" && (string)($_GET['show'] ?? '') === '1');
$newsTypesActive  = ($section==="01" && $page==="01" && $sec_page==="03");
$newsUsersActive  = ($section==="01" && $page==="01" && $sec_page==="05");

$statTextsActive  = ($section==="01" && $page==="02" && $sec_page==="02" && empty($_GET['show']));
$statTextsAdd     = ($section==="01" && $page==="02" && $sec_page==="02" && (string)($_GET['show'] ?? '') === '1');
$statExprActive   = ($section==="01" && $page==="02" && $sec_page==="03" && empty($_GET['show']));
$statExprAdd      = ($section==="01" && $page==="02" && $sec_page==="03" && (string)($_GET['show'] ?? '') === '1');

$contactsStoresActive  = ($section==="01" && $page==="03" && $sec_page==="01");
$marketsActive         = ($section==="01" && $page==="03" && $sec_page==="02");
$wholesaleActive       = ($section==="01" && $page==="03" && $sec_page==="03");
$representativesAct    = ($section==="01" && $page==="03" && $sec_page==="04");
$openingHoursActive    = ($section==="01" && $page==="03" && $sec_page==="05");
?>

<div class="text-uppercase small fw-semibold text-muted mt-2 mb-1">Hlavní menu</div>

<!-- NOVINKY -->
<a class="nav-link d-flex align-items-center <?= $isNewsOpen ? 'active' : '' ?>"
   href="#<?= $collapseNewsId ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isNewsOpen ? 'true' : 'false' ?>"
   aria-controls="<?= $collapseNewsId ?>">
    <i class="bi bi-newspaper me-2"></i>
    <span>Novinky</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isNewsOpen ? 'show' : '' ?>" id="<?= $collapseNewsId ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $newsListActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=01&amp;sec_page=02">Výpis novinek</a>

        <a class="nav-link py-1 <?= $newsAddActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;show=1">Přidat novinku</a>

        <a class="nav-link py-1 <?= $newsTypesActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=01&amp;sec_page=03">Typy novinek</a>

        <a class="nav-link py-1 <?= $newsUsersActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=01&amp;sec_page=05">Uživatelé newsletteru</a>
    </div>
</div>

<!-- STATICKÉ TEXTY -->
<a class="nav-link d-flex align-items-center <?= $isStaticOpen ? 'active' : '' ?>"
   href="#<?= $collapseTextyId ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isStaticOpen ? 'true' : 'false' ?>"
   aria-controls="<?= $collapseTextyId ?>">
    <i class="bi bi-card-text me-2"></i>
    <span>Statické texty, výrazy</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isStaticOpen ? 'show' : '' ?>" id="<?= $collapseTextyId ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $statTextsActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=02&amp;sec_page=02">Výpis statických textů</a>

        <a class="nav-link py-1 <?= $statTextsAdd ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;show=1">Přidat statický text</a>

        <a class="nav-link py-1 <?= $statExprActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=02&amp;sec_page=03">Výpis statických výrazů</a>

        <a class="nav-link py-1 <?= $statExprAdd ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;show=1">Přidat statický výraz</a>
    </div>
</div>

<!-- KONTAKTY -->
<a class="nav-link d-flex align-items-center <?= $isContactsOpen ? 'active' : '' ?>"
   href="#<?= $collapseContactsId ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isContactsOpen ? 'true' : 'false' ?>"
   aria-controls="<?= $collapseContactsId ?>">
    <i class="bi bi-person-lines-fill me-2"></i>
    <span>Kontakty</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isContactsOpen ? 'show' : '' ?>" id="<?= $collapseContactsId ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $contactsStoresActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=03&amp;sec_page=01">Prodejny</a>
        <a class="nav-link py-1 <?= $marketsActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=03&amp;sec_page=02">Markety</a>
        <a class="nav-link py-1 <?= $wholesaleActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=03&amp;sec_page=03">Velkoobchody</a>
        <a class="nav-link py-1 <?= $representativesAct ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=03&amp;sec_page=04">Obchodní zástupci</a>
        <a class="nav-link py-1 <?= $openingHoursActive ? 'active' : '' ?>"
           href="index.php?section=01&amp;page=03&amp;sec_page=05">Otevírací doby</a>
    </div>
</div>
