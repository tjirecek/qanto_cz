<?php
global $section, $page, $sec_page;

$section  = $section  ?? '';
$page     = $page     ?? '';
$sec_page = $sec_page ?? '';

$adminUserPrava = (string)admin_session_prava();
if (!in_array($adminUserPrava, ['1','2'], true)) {
    return;
}

$projectItems = [
    '01' => ['icon' => 'bi-briefcase', 'label' => '01 Volná místa'],
    '02' => ['icon' => 'bi-tags', 'label' => '02 Akční nabídky'],
    '03' => ['icon' => 'bi-badge-ad', 'label' => '03 Bannery'],
    '04' => ['icon' => 'bi-person-raised-hand', 'label' => '04 Brigádníci'],
    '05' => ['icon' => 'bi-music-note-beamed', 'label' => '05 Ples'],
    '06' => ['icon' => 'bi-telephone', 'label' => '06 Volání'],
    '07' => ['icon' => 'bi-trophy', 'label' => '07 TenisQcup'],
    '08' => ['icon' => 'bi-clipboard-data', 'label' => '08 Inventury'],
];
?>

<div class="text-uppercase small fw-semibold text-muted mt-3 mb-1">Project menu qanto.cz</div>

<?php foreach ($projectItems as $itemPage => $item): ?>
    <?php $active = ($section === '02' && $page === $itemPage); ?>
    <a class="nav-link d-flex align-items-center <?= $active ? 'active' : '' ?>"
       href="index.php?section=02&amp;page=<?= htmlspecialchars($itemPage, ENT_QUOTES, 'UTF-8') ?>&amp;sec_page=01">
        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i>
        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
    </a>
<?php endforeach; ?>
