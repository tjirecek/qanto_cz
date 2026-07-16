<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$staticTextCode = trim((string)($staticTextCode ?? ''));
$staticTextTitle = $staticTextCode !== '' ? stat_text($staticTextCode, $lang, 'nazev') : null;
$staticTextBody = $staticTextCode !== '' ? stat_text($staticTextCode, $lang) : null;
$detailSidebarItems = function_exists('frontend_detail_sidebar_ads') ? frontend_detail_sidebar_ads($lang, 3) : [];

if ($staticTextTitle === null || $staticTextTitle === '') {
    $staticTextTitle = (string)($pagetitle ?? ui_text('site.name', 'Qanto'));
    $staticTextTitle = preg_replace('~\s*\|\s*Qanto$~', '', $staticTextTitle) ?: $staticTextTitle;
}
?>
<section class="static-text-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= htmlspecialchars(ui_text('aria.breadcrumb', 'Drobečková navigace'), ENT_QUOTES, 'UTF-8') ?>">
            <ol>
                <li>
                    <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.home', 'Domů'), ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M4 10.75 12 4l8 6.75V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.25Z"></path>
                        </svg>
                    </a>
                </li>
                <li><span aria-current="page"><?= htmlspecialchars($staticTextTitle, ENT_QUOTES, 'UTF-8') ?></span></li>
            </ol>
        </nav>
        <div class="detail-page-layout">
            <article class="static-text-page__panel detail-page-content">
                <h1><?= htmlspecialchars($staticTextTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="static-text-page__content">
                    <?= $staticTextBody !== null && $staticTextBody !== '' ? $staticTextBody : '<p>' . htmlspecialchars(ui_text('common.text_unavailable', 'Text není aktuálně dostupný.'), ENT_QUOTES, 'UTF-8') . '</p>' ?>
                </div>
            </article>
            <?php include __DIR__ . '/detail_sidebar.php'; ?>
        </div>
    </div>
</section>
