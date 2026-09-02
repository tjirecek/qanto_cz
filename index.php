<?php
declare(strict_types=1);

require_once __DIR__ . '/functions/bootstrap.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/mysql_connect.php';
require_once __DIR__ . '/functions/settings.php';

$lang = (string)($lang ?? 'cz');
if (!in_array($lang, ['cz', 'en'], true)) {
    $lang = 'cz';
}

require_once ROOT_DIR . '/functions/lang.php';
require_once ROOT_DIR . '/functions/fun_default.php';
require_once ROOT_DIR . '/functions/fun_frontend_captcha.php';
require_once ROOT_DIR . '/functions/fun_news.php';
require_once ROOT_DIR . '/functions/fun_rep_bannery.php';
require_once ROOT_DIR . '/functions/fun_rep_akce_unsubscribe.php';
require_once ROOT_DIR . '/functions/fun_rep_tenis_qcup_front.php';
require_once ROOT_DIR . '/functions/fun_rep_volna_mista_front.php';
require_once ROOT_DIR . '/functions/fun_rep_brigadnici_front.php';
require_once ROOT_DIR . '/functions/fun_contacts_front.php';
require_once ROOT_DIR . '/functions/fun_markety_front.php';
require_once ROOT_DIR . '/functions/fun_velkoobchod_front.php';
require_once ROOT_DIR . '/functions/fun_rep_volani_front.php';
require_once ROOT_DIR . '/functions/pages_include.php';

$page = (string)($page ?? 'main');
if (!preg_match('~^[a-z0-9_]+$~i', $page)) {
    http_response_code(400);
    $page = 'main';
    $contentError = ui_text('common.bad_page');
}

$file = INC_DIR . "/{$page}.php";
$htmlLang = $lang === 'en' ? 'en' : 'cs';
$metaDescription = trim((string)($metaDescription ?? ($word[3] ?? '')));
$bodyClass = 'site-page site-page--' . preg_replace('~[^a-z0-9_-]+~i', '-', $page);
if ($page === 'akce' && isset($_GET['akce']) && (int)$_GET['akce'] > 0) {
    $bodyClass .= ' site-page--akce-detail';
}

$footerLangPrefix = '/' . rawurlencode($lang);
$footerLinkGroups = [
    [
        'label' => ui_text('nav.akce'),
        'url' => $footerLangPrefix . '/akce',
        'links' => [],
    ],
    [
        'label' => ui_text('footer.branches_title'),
        'url' => '',
        'links' => [
            [
                'label' => ui_text('nav.markety'),
                'url' => $footerLangPrefix . '/markety',
            ],
            [
                'label' => ui_text('footer.velkoobchody'),
                'url' => $footerLangPrefix . '/velkoobchod',
            ],
            [
                'label' => ui_text('nav.prodejny'),
                'url' => $footerLangPrefix . '/prodejny',
            ],
        ],
    ],
    [
        'label' => ui_text('nav.kariera'),
        'url' => '',
        'links' => [
            [
                'label' => ui_text('footer.jobs'),
                'url' => $footerLangPrefix . '/kariera',
            ],
            [
                'label' => ui_text('footer.brigada'),
                'url' => $footerLangPrefix . '/brigada',
            ],
        ],
    ],
    [
        'label' => ui_text('nav.kontakt'),
        'url' => $footerLangPrefix . '/kontakty',
        'links' => [],
    ],
];
$footerSocialLinks = [
    [
        'label' => ui_text('footer.facebook_wholesale'),
        'url' => stat_vyraz('footer.social.facebook_velkoobchod.url', $lang) ?: '#',
        'network' => 'facebook',
    ],
    [
        'label' => ui_text('footer.facebook_retail'),
        'url' => stat_vyraz('footer.social.facebook_maloobchod.url', $lang) ?: '#',
        'network' => 'facebook',
    ],
    [
        'label' => ui_text('footer.instagram_wholesale'),
        'url' => stat_vyraz('footer.social.instagram_velkoobchod.url', $lang) ?: '#',
        'network' => 'instagram',
    ],
    [
        'label' => ui_text('footer.instagram_retail'),
        'url' => stat_vyraz('footer.social.instagram_maloobchod.url', $lang) ?: '#',
        'network' => 'instagram',
    ],
];
$footerOtherLinks = [
    [
        'label' => ui_text('footer.business_terms'),
        'url' => $footerLangPrefix . '/obchodni-podminky',
    ],
    [
        'label' => ui_text('footer.edi'),
        'url' => $footerLangPrefix . '/elektronicka-vymena-dat',
    ],
    [
        'label' => ui_text('footer.customer_cards'),
        'url' => $footerLangPrefix . '/zakaznicke-karty',
    ],
];

$frontendConfig = app_bootstrap_config();
$frontendCookiesSpravneKey = trim((string)($frontendConfig['frontend_cookies_spravne_key'] ?? ''));
$frontendMapProvider = trim((string)($frontendConfig['frontend_map_provider'] ?? 'mapy'));
$frontendMapyApiKey = trim((string)($frontendConfig['frontend_mapy_api_key'] ?? ''));
$frontendMapyMapset = trim((string)($frontendConfig['frontend_mapy_mapset'] ?? 'basic'));
$frontendMapyRetina = (bool)($frontendConfig['frontend_mapy_retina'] ?? true);

if (!in_array($frontendMapyMapset, ['basic', 'outdoor', 'winter', 'aerial'], true)) {
    $frontendMapyMapset = 'basic';
}

$frontendMapConfig = [
    'provider' => $frontendMapProvider === 'mapy' && $frontendMapyApiKey !== '' ? 'mapy' : 'carto',
    'mapy' => [
        'apiKey' => $frontendMapyApiKey,
        'mapset' => $frontendMapyMapset,
        'tileSize' => $frontendMapyRetina && in_array($frontendMapyMapset, ['basic', 'outdoor'], true) ? '256@2x' : '256',
        'lang' => $lang === 'en' ? 'en' : 'cs',
    ],
];

if ($page === 'volani' && (string)($_GET['pdf'] ?? '') === '1') {
    frontend_volani_stream_pdf($pdo, trim((string)($_GET['unify'] ?? '')), isset($_GET['typ']) ? (int)$_GET['typ'] : 1);
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (trim((string)($metaRobots ?? '')) !== ''): ?>
        <meta name="robots" content="<?= htmlspecialchars((string)$metaRobots, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <meta name="author" content="Astur & Qanto s.r.o.">
    <title><?= htmlspecialchars((string)($pagetitle ?? 'Qanto'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/favicon.png" type="image/png" sizes="256x256">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,500;8..60,650&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_version('assets/lib/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_version('assets/css/default.css') ?>">
    <?php if ($frontendCookiesSpravneKey !== ''): ?>
        <script id="cookies-spravne" src="https://cookies-spravne.cz/static/cc?key=<?= htmlspecialchars(rawurlencode($frontendCookiesSpravneKey), ENT_QUOTES, 'UTF-8') ?>" async defer></script>
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">
<header class="site-header" data-site-header>
    <?php include INC_DIR . '/nav.php'; ?>
</header>

<main class="site-main">
    <?php if (isset($contentError)): ?>
        <div class="container py-5"><div class="alert alert-danger"><?= htmlspecialchars($contentError, ENT_QUOTES, 'UTF-8') ?></div></div>
    <?php elseif (is_file($file)): ?>
        <?php include $file; ?>
    <?php else: ?>
        <?php http_response_code(404); ?>
        <div class="container py-5"><div class="alert alert-warning"><?= htmlspecialchars(ui_text('common.page_not_found'), ENT_QUOTES, 'UTF-8') ?></div></div>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <div class="site-shell">
        <div class="site-footer__top">
            <a class="site-footer__brand" href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('site.name'), ENT_QUOTES, 'UTF-8') ?>">
                <img src="/img/design/logo_qanto_light.webp" width="634" height="178" alt="Qanto">
            </a>
            <p><?= htmlspecialchars(stat_vyraz_text('footer.claim'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="site-footer__main">
            <nav class="site-footer__nav" aria-label="<?= htmlspecialchars(ui_text('aria.footer_navigation'), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($footerLinkGroups as $footerLinkGroup): ?>
                    <?php $footerGroupUrl = trim((string)($footerLinkGroup['url'] ?? '')); ?>
                    <div class="site-footer__link-group">
                        <?php if ($footerGroupUrl !== ''): ?>
                            <a class="site-footer__link-title" href="<?= htmlspecialchars($footerGroupUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$footerLinkGroup['label'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <strong><?= htmlspecialchars((string)$footerLinkGroup['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($footerLinkGroup['links'])): ?>
                            <div class="site-footer__link-list">
                                <?php foreach ($footerLinkGroup['links'] as $footerLink): ?>
                                    <a href="<?= htmlspecialchars((string)$footerLink['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$footerLink['label'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>

            <nav class="site-footer__other" aria-label="<?= htmlspecialchars(ui_text('footer.other_title'), ENT_QUOTES, 'UTF-8') ?>">
                <strong><?= htmlspecialchars(ui_text('footer.other_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="site-footer__other-list">
                    <?php foreach ($footerOtherLinks as $footerOtherLink): ?>
                        <a href="<?= htmlspecialchars((string)$footerOtherLink['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$footerOtherLink['label'], ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <nav class="site-footer__social" aria-label="<?= htmlspecialchars(ui_text('aria.social_navigation'), ENT_QUOTES, 'UTF-8') ?>">
                <strong><?= htmlspecialchars(ui_text('footer.social_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="site-footer__social-list">
                    <?php foreach ($footerSocialLinks as $socialLink): ?>
                        <?php
                        $socialUrl = trim((string)$socialLink['url']);
                        $socialNetwork = (string)($socialLink['network'] ?? '');
                        ?>
                        <a href="<?= htmlspecialchars($socialUrl !== '' ? $socialUrl : '#', ENT_QUOTES, 'UTF-8') ?>"<?= str_starts_with($socialUrl, 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                            <span class="site-footer__social-icon" aria-hidden="true">
                                <?php if ($socialNetwork === 'facebook'): ?>
                                    <svg viewBox="0 0 24 24" focusable="false"><path d="M14.2 8.2V6.7c0-.7.5-.9.9-.9h2.2V2.1L14.2 2c-3.5 0-4.3 2.6-4.3 4.3v1.9H7.1V12h2.8v10h4.1V12h3.1l.5-3.8h-3.4Z"/></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" focusable="false"><path d="M7.3 2h9.4A5.3 5.3 0 0 1 22 7.3v9.4a5.3 5.3 0 0 1-5.3 5.3H7.3A5.3 5.3 0 0 1 2 16.7V7.3A5.3 5.3 0 0 1 7.3 2Zm0 2A3.3 3.3 0 0 0 4 7.3v9.4A3.3 3.3 0 0 0 7.3 20h9.4a3.3 3.3 0 0 0 3.3-3.3V7.3A3.3 3.3 0 0 0 16.7 4H7.3Zm4.7 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8Zm0 2a2.9 2.9 0 1 0 0 5.8 2.9 2.9 0 0 0 0-5.8Zm5.2-2.2a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2Z"/></svg>
                                <?php endif; ?>
                            </span>
                            <?= htmlspecialchars((string)$socialLink['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>

        <div class="site-footer__bottom">
            <div class="site-footer__copy">&copy; <?= date('Y') ?> <?= htmlspecialchars(ui_text('footer.copy'), ENT_QUOTES, 'UTF-8') ?></div>
            <nav class="site-footer__legal" aria-label="<?= htmlspecialchars(ui_text('footer.legal_title'), ENT_QUOTES, 'UTF-8') ?>">
                <a href="https://cookies-spravne.cz/cookie-policy?key=XUx6mkmT3Ba72MZ&amp;lang=cs" target="_blank" rel="noopener"><?= htmlspecialchars(ui_text('footer.cookies'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= htmlspecialchars($footerLangPrefix . '/osobni-udaje', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('footer.privacy'), ENT_QUOTES, 'UTF-8') ?></a>
            </nav>
        </div>
    </div>
</footer>

<button type="button" class="scroll-top" data-scroll-top aria-label="<?= htmlspecialchars(ui_text('aria.scroll_top'), ENT_QUOTES, 'UTF-8') ?>">↑</button>
<script src="<?= asset_version('assets/lib/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
window.qantoMapConfig = <?= json_encode($frontendMapConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= asset_version('assets/js/default.js') ?>"></script>
</body>
</html>
