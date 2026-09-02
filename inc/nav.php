<?php
declare(strict_types=1);

$currentSection = (string)($currentSection ?? 'main');
$lang = (string)($lang ?? 'cz');
if (!in_array($lang, ['cz', 'en'], true)) {
    $lang = 'cz';
}

$menuItems = [
    ['section' => 'akce', 'label' => ui_text('nav.akce')],
    ['section' => 'markety', 'label' => ui_text('nav.markety')],
    ['section' => 'velkoobchod', 'label' => ui_text('nav.velkoobchod')],
    ['section' => 'prodejny', 'label' => ui_text('nav.prodejny')],
    ['section' => 'kariera', 'label' => ui_text('nav.kariera')],
];

$aboutItems = [
    ['section' => 'o-nas', 'label' => ui_text('nav.o_nas')],
    ['section' => 'historie', 'label' => ui_text('nav.historie')],
    ['section' => 'media', 'label' => ui_text('nav.media')],
    ['section' => 'podporujeme', 'label' => ui_text('nav.podporujeme')],
];

$aboutSections = array_column($aboutItems, 'section');
$aboutActive = in_array($currentSection, $aboutSections, true);

$buildUrl = static function (string $section) use ($lang): string {
    return '/' . rawurlencode($lang) . ($section === 'main' ? '' : '/' . rawurlencode($section));
};

$buildLangUrl = static function (string $targetLang) use ($currentSection): string {
    return '/' . rawurlencode($targetLang) . ($currentSection === 'main' ? '' : '/' . rawurlencode($currentSection));
};
?>
<nav class="site-nav navbar navbar-expand-xl" aria-label="<?= htmlspecialchars(ui_text('aria.main_navigation'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="site-shell site-topbar">
        <a class="site-brand" href="<?= htmlspecialchars($buildUrl('main'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.home'), ENT_QUOTES, 'UTF-8') ?>">
            <img src="/img/design/logo_qanto.webp" width="634" height="178" alt="Qanto">
        </a>
        <div class="site-topbar__actions">
            <div class="site-lang" aria-label="<?= htmlspecialchars(ui_text('aria.language'), ENT_QUOTES, 'UTF-8') ?>">
                <a class="<?= $lang === 'cz' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildLangUrl('cz'), ENT_QUOTES, 'UTF-8') ?>">CZ</a>
                <a class="<?= $lang === 'en' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildLangUrl('en'), ENT_QUOTES, 'UTF-8') ?>">EN</a>
            </div>
            <a class="site-topbar__icon" href="<?= htmlspecialchars($buildUrl('kontakty'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.contact'), ENT_QUOTES, 'UTF-8') ?>">
                <span></span>
            </a>
            <button class="site-nav__toggle navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNavigation" aria-controls="siteNavigation" aria-expanded="false" aria-label="<?= htmlspecialchars(ui_text('aria.open_menu'), ENT_QUOTES, 'UTF-8') ?>">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
    <div class="site-menu-wrap">
        <div class="site-shell">
            <div class="collapse navbar-collapse site-nav__collapse" id="siteNavigation">
                <div class="site-menu">
                    <?php foreach ($menuItems as $item): ?>
                        <?php $active = $currentSection === $item['section']; ?>
                        <a class="site-menu__item<?= $active ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildUrl($item['section']), ENT_QUOTES, 'UTF-8') ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                    <div class="dropdown site-menu__dropdown">
                        <button class="site-menu__item site-menu__button dropdown-toggle<?= $aboutActive ? ' is-active' : '' ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars(ui_text('nav.o_nas'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <ul class="dropdown-menu site-menu__dropdown-menu">
                            <?php foreach ($aboutItems as $item): ?>
                                <?php $active = $currentSection === $item['section']; ?>
                                <li>
                                    <a class="dropdown-item<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars($buildUrl($item['section']), ENT_QUOTES, 'UTF-8') ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a class="site-menu__item<?= $currentSection === 'kontakty' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildUrl('kontakty'), ENT_QUOTES, 'UTF-8') ?>"<?= $currentSection === 'kontakty' ? ' aria-current="page"' : '' ?>><?= htmlspecialchars(ui_text('nav.kontakt'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </div>
    </div>
</nav>
