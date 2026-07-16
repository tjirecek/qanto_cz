<?php
global $section, $page, $sec_page, $MENU_ID_PREFIX;

$section  = $section  ?? '';
$page     = $page     ?? '';
$sec_page = $sec_page ?? '';

$adminUserPrava = (string)admin_session_prava();
if (!in_array($adminUserPrava, ['1','2'], true)) {
    return;
}

$MENU_ID_PREFIX = $MENU_ID_PREFIX ?? 'nav';
$collapseVolnaMistaId = $MENU_ID_PREFIX . '_collapseProjectVolnaMista';
$collapseAkceId = $MENU_ID_PREFIX . '_collapseProjectAkce';
$collapseBrigadniciId = $MENU_ID_PREFIX . '_collapseProjectBrigadnici';
$collapseZavozId = $MENU_ID_PREFIX . '_collapseProjectZavoz';

$isVolnaMistaOpen = ($section === '02' && $page === '01');
$isAkceOpen = ($section === '02' && $page === '02');
$isBrigadniciOpen = ($section === '02' && $page === '04');
$isZavozOpen = ($section === '02' && $page === '09');
$volnaMistaPoziceActive = ($section === '02' && $page === '01' && in_array($sec_page, ['01', '04', '05'], true));
$volnaMistaSkupinyActive = ($section === '02' && $page === '01' && in_array($sec_page, ['02', '06', '07'], true));
$volnaMistaDotaznikyActive = ($section === '02' && $page === '01' && $sec_page === '03');
$akceOffersActive = ($section === '02' && $page === '02' && in_array($sec_page, ['01', '03', '05'], true));
$akceTypesActive = ($section === '02' && $page === '02' && in_array($sec_page, ['02', '04'], true));
$akceUsersActive = ($section === '02' && $page === '02' && $sec_page === '06');
$brigadniciVoActive = ($section === '02' && $page === '04' && $sec_page === '01');
$brigadniciMoActive = ($section === '02' && $page === '04' && $sec_page === '02');
$zavozObceActive = ($section === '02' && $page === '09' && $sec_page === '01');
$zavozMapaActive = ($section === '02' && $page === '09' && $sec_page === '02');
$zavozOkresyActive = ($section === '02' && $page === '09' && $sec_page === '03');

$projectItemsBeforeBrigadnici = [
    '03' => ['icon' => 'bi-badge-ad', 'label' => '03 Bannery'],
];

$projectItemsAfterBrigadnici = [
    '05' => ['icon' => 'bi-music-note-beamed', 'label' => '05 Ples'],
    '06' => ['icon' => 'bi-telephone', 'label' => '06 Volání'],
    '07' => ['icon' => 'bi-trophy', 'label' => '07 TenisQcup'],
    '08' => ['icon' => 'bi-clipboard-data', 'label' => '08 Inventury'],
];
?>

<div class="text-uppercase small fw-semibold text-muted mt-3 mb-1">Project menu qanto.cz</div>

<a class="nav-link d-flex align-items-center <?= $isVolnaMistaOpen ? 'active' : '' ?>"
   href="#<?= htmlspecialchars($collapseVolnaMistaId, ENT_QUOTES, 'UTF-8') ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isVolnaMistaOpen ? 'true' : 'false' ?>"
   aria-controls="<?= htmlspecialchars($collapseVolnaMistaId, ENT_QUOTES, 'UTF-8') ?>">
    <i class="bi bi-briefcase me-2"></i>
    <span>01 Volná místa</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isVolnaMistaOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($collapseVolnaMistaId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $volnaMistaPoziceActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=01&amp;sec_page=01">Pozice</a>

        <a class="nav-link py-1 <?= $volnaMistaSkupinyActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=01&amp;sec_page=02">Skupiny</a>

        <a class="nav-link py-1 <?= $volnaMistaDotaznikyActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=01&amp;sec_page=03">Dotazníky</a>
    </div>
</div>

<a class="nav-link d-flex align-items-center <?= $isAkceOpen ? 'active' : '' ?>"
   href="#<?= htmlspecialchars($collapseAkceId, ENT_QUOTES, 'UTF-8') ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isAkceOpen ? 'true' : 'false' ?>"
   aria-controls="<?= htmlspecialchars($collapseAkceId, ENT_QUOTES, 'UTF-8') ?>">
    <i class="bi bi-tags me-2"></i>
    <span>02 Akční nabídky</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isAkceOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($collapseAkceId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $akceOffersActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=02&amp;sec_page=01">Akce</a>

        <a class="nav-link py-1 <?= $akceTypesActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=02&amp;sec_page=02">Typy</a>

        <a class="nav-link py-1 <?= $akceUsersActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=02&amp;sec_page=06">Odběratelé</a>
    </div>
</div>

<?php foreach ($projectItemsBeforeBrigadnici as $itemPage => $item): ?>
    <?php $active = ($section === '02' && $page === $itemPage); ?>
    <a class="nav-link d-flex align-items-center <?= $active ? 'active' : '' ?>"
       href="index.php?section=02&amp;page=<?= htmlspecialchars($itemPage, ENT_QUOTES, 'UTF-8') ?>&amp;sec_page=01">
        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i>
        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
    </a>
<?php endforeach; ?>

<a class="nav-link d-flex align-items-center <?= $isBrigadniciOpen ? 'active' : '' ?>"
   href="#<?= htmlspecialchars($collapseBrigadniciId, ENT_QUOTES, 'UTF-8') ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isBrigadniciOpen ? 'true' : 'false' ?>"
   aria-controls="<?= htmlspecialchars($collapseBrigadniciId, ENT_QUOTES, 'UTF-8') ?>">
    <i class="bi bi-person-raised-hand me-2"></i>
    <span>04 Brigádníci</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isBrigadniciOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($collapseBrigadniciId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $brigadniciVoActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=04&amp;sec_page=01">VO</a>

        <a class="nav-link py-1 <?= $brigadniciMoActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=04&amp;sec_page=02">MO</a>
    </div>
</div>

<?php foreach ($projectItemsAfterBrigadnici as $itemPage => $item): ?>
    <?php $active = ($section === '02' && $page === $itemPage); ?>
    <a class="nav-link d-flex align-items-center <?= $active ? 'active' : '' ?>"
       href="index.php?section=02&amp;page=<?= htmlspecialchars($itemPage, ENT_QUOTES, 'UTF-8') ?>&amp;sec_page=01">
        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i>
        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
    </a>
<?php endforeach; ?>

<a class="nav-link d-flex align-items-center <?= $isZavozOpen ? 'active' : '' ?>"
   href="#<?= htmlspecialchars($collapseZavozId, ENT_QUOTES, 'UTF-8') ?>"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= $isZavozOpen ? 'true' : 'false' ?>"
   aria-controls="<?= htmlspecialchars($collapseZavozId, ENT_QUOTES, 'UTF-8') ?>">
    <i class="bi bi-geo-alt me-2"></i>
    <span>09 Závozové obce</span>
    <i class="bi bi-chevron-down ms-auto small"></i>
</a>

<div class="collapse <?= $isZavozOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($collapseZavozId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="nav flex-column ms-4">
        <a class="nav-link py-1 <?= $zavozObceActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=09&amp;sec_page=01">Obce</a>

        <a class="nav-link py-1 <?= $zavozMapaActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=09&amp;sec_page=02">Mapa</a>

        <a class="nav-link py-1 <?= $zavozOkresyActive ? 'active' : '' ?>"
           href="index.php?section=02&amp;page=09&amp;sec_page=03">Okresy</a>
    </div>
</div>
