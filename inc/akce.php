<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$offerId = isset($_GET['akce']) ? (int)$_GET['akce'] : 0;
$requestedFlyerType = preg_replace('~[^a-z0-9_-]+~i', '', (string)($_GET['typ'] ?? '')) ?? '';
$offer = $offerId > 0 && function_exists('frontend_akce_offer_detail') ? frontend_akce_offer_detail($offerId, $lang) : null;
$overview = function_exists('frontend_akce_page_overview') ? frontend_akce_page_overview($lang, 0) : [
    'current_panels' => [],
    'upcoming_panels' => [],
    'archive_panels' => [],
];
$subscribeResult = null;
$subscribeTypes = function_exists('frontend_akce_subscription_types') ? frontend_akce_subscription_types($lang) : [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['action'] ?? '') === 'akce_subscribe' && function_exists('frontend_akce_subscribe_save')) {
    $subscribeResult = frontend_akce_subscribe_save($_POST);
}

$subscribeToken = function_exists('frontend_akce_subscribe_token') ? frontend_akce_subscribe_token() : '';
$postedTypeIds = $_POST['type_ids'] ?? [];
if (!is_array($postedTypeIds)) {
    $postedTypeIds = [];
}
$selectedSubscribeTypes = [];
foreach ($postedTypeIds as $postedTypeId) {
    $selectedSubscribeTypes[(int)$postedTypeId] = true;
}
$selectAllSubscribeTypes = $selectedSubscribeTypes === [] || (is_array($subscribeResult) && (bool)($subscribeResult['ok'] ?? false));

$renderFlyerCard = static function (array $item): void {
    $validityText = function_exists('frontend_akce_validity_text')
        ? frontend_akce_validity_text($item)
        : '';
    $status = preg_replace('~[^a-z0-9_-]+~i', '', (string)($item['status'] ?? 'valid'));
    ?>
    <article class="flyer-card">
        <a class="flyer-card__image<?= (string)($item['image'] ?? '') === '' ? ' flyer-card__image--empty' : '' ?>" href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if ((string)($item['image'] ?? '') !== ''): ?>
                <img src="<?= htmlspecialchars((string)$item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
            <?php else: ?>
                <span><?= htmlspecialchars(ui_text('flyers.preview_missing', 'Náhled se připravuje'), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </a>
        <div class="flyer-card__body">
            <div class="flyer-card__badges">
                <span class="flyer-card__status flyer-card__status--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$item['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ((string)($item['type_label'] ?? '') !== ''): ?>
                    <span class="flyer-card__type <?= htmlspecialchars((string)($item['type_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$item['type_label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if ($validityText !== ''): ?>
                <p><?= htmlspecialchars($validityText, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <div class="flyer-card__actions">
                <a href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('flyers.browse', 'Prolistovat'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php if ((string)($item['pdf'] ?? '') !== ''): ?>
                    <a href="<?= htmlspecialchars((string)$item['pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(ui_text('flyers.pdf', 'PDF'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
};

$renderSubscribeBlock = static function (string $modifier = '') use ($subscribeTypes, $subscribeResult, $subscribeToken, $selectAllSubscribeTypes, $selectedSubscribeTypes): void {
    if ($subscribeTypes === []) {
        return;
    }
    $class = trim('akce-subscribe ' . $modifier);
    ?>
    <section class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="akce-subscribe-title">
        <div class="akce-subscribe__content">
            <span><?= htmlspecialchars(ui_text('flyers.subscribe_kicker', 'Odběr letáků'), ENT_QUOTES, 'UTF-8') ?></span>
            <h2 id="akce-subscribe-title"><?= htmlspecialchars(ui_text('flyers.subscribe_title', 'Nový leták vám pošleme přímo na e-mail'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(ui_text('flyers.subscribe_text', 'Vyberte si typy letáků, které chcete dostávat.'), ENT_QUOTES, 'UTF-8') ?></p>

            <?php if (is_array($subscribeResult)): ?>
                <div class="akce-subscribe__message <?= (bool)($subscribeResult['ok'] ?? false) ? 'is-success' : 'is-error' ?>" role="status">
                    <?= htmlspecialchars((string)($subscribeResult['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" class="akce-subscribe__form">
                <input type="hidden" name="action" value="akce_subscribe">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($subscribeToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="akce-subscribe__row">
                    <label class="visually-hidden" for="akce_subscribe_email"><?= htmlspecialchars(ui_text('flyers.subscribe_email', 'Váš e-mail'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email"
                           name="email"
                           id="akce_subscribe_email"
                           required
                           autocomplete="email"
                           placeholder="<?= htmlspecialchars(ui_text('flyers.subscribe_email', 'Váš e-mail'), ENT_QUOTES, 'UTF-8') ?>"
                           value="<?= is_array($subscribeResult) && !(bool)($subscribeResult['ok'] ?? false) ? htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                    <button type="submit"><?= htmlspecialchars(ui_text('flyers.subscribe_button', 'Odebírat'), ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">›</span></button>
                </div>

                <div class="akce-subscribe__types" aria-label="<?= htmlspecialchars(ui_text('flyers.subscribe_types', 'Typy letáků'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($subscribeTypes as $type): ?>
                        <?php
                        $typeId = (int)$type['id'];
                        $checked = $selectAllSubscribeTypes || isset($selectedSubscribeTypes[$typeId]);
                        ?>
                        <label class="akce-subscribe__type">
                            <input type="checkbox" name="type_ids[]" value="<?= $typeId ?>" <?= $checked ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars((string)$type['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if (function_exists('frontend_captcha_render')): ?>
                    <?php frontend_captcha_render('akce_subscribe', 'akce-subscribe-' . ($modifier !== '' ? 'side' : 'main')); ?>
                <?php endif; ?>

                <p class="akce-subscribe__consent">
                    <?= htmlspecialchars(ui_text('flyers.subscribe_consent', 'Odběrem souhlasíte se'), ENT_QUOTES, 'UTF-8') ?>
                    <a href="/<?= htmlspecialchars((string)($GLOBALS['lang'] ?? 'cz'), ENT_QUOTES, 'UTF-8') ?>/osobni-udaje">
                        <?= htmlspecialchars(ui_text('flyers.subscribe_privacy_link', 'zpracováním osobních údajů'), ENT_QUOTES, 'UTF-8') ?>
                    </a>.
                </p>
            </form>
        </div>
        <div class="akce-subscribe__mark" aria-hidden="true"></div>
    </section>
    <?php
};

$renderPanelSection = static function (string $sectionId, string $titleKey, string $titleFallback, string $textKey, string $textFallback, array $panels, string $emptyKey, string $emptyFallback, string $modifier = '', bool $showFilters = true) use ($renderFlyerCard, $requestedFlyerType): void {
    $class = trim('akce-list-section ' . $modifier);
    $itemsPerPage = 35;
    $visiblePanels = array_values($panels);
    if (!$showFilters && $panels !== []) {
        $visiblePanels = [array_values($panels)[0]];
    }
    $activePanelId = (string)($visiblePanels[0]['id'] ?? '');
    if ($showFilters && $requestedFlyerType !== '') {
        foreach ($visiblePanels as $panel) {
            if ((string)($panel['id'] ?? '') === $requestedFlyerType) {
                $activePanelId = $requestedFlyerType;
                break;
            }
        }
    }
    ?>
    <section class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="<?= htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8') ?>-title" data-home-flyers>
        <div class="akce-list-section__head">
            <div>
                <h2 id="<?= htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8') ?>-title"><?= htmlspecialchars(ui_text($titleKey, $titleFallback), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(ui_text($textKey, $textFallback), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <?php if ($visiblePanels !== []): ?>
            <?php if ($showFilters): ?>
                <div class="home-flyers__tabs akce-list-section__tabs" role="tablist" aria-label="<?= htmlspecialchars(ui_text('flyers.category', 'Kategorie'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($visiblePanels as $panelIndex => $panel): ?>
                        <?php
                        $panelId = (string)$panel['id'];
                        $isActivePanel = $panelId === $activePanelId;
                        $tabId = $sectionId . '-tab-' . preg_replace('~[^a-z0-9_-]+~i', '-', $panelId);
                        $tabClass = trim('home-flyers__tab ' . ($isActivePanel ? 'is-active ' : '') . (string)($panel['class'] ?? ''));
                        ?>
                        <button type="button"
                                id="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') ?>"
                                class="<?= htmlspecialchars($tabClass, ENT_QUOTES, 'UTF-8') ?>"
                                data-flyer-type="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') ?>"
                                role="tab"
                                aria-selected="<?= $isActivePanel ? 'true' : 'false' ?>">
                            <?= htmlspecialchars((string)$panel['label'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="home-flyers__panels">
                <?php foreach ($visiblePanels as $panelIndex => $panel): ?>
                    <?php
                    $panelId = (string)$panel['id'];
                    $isActivePanel = $panelId === $activePanelId;
                    $items = array_values((array)($panel['items'] ?? []));
                    $pages = array_chunk($items, $itemsPerPage);
                    ?>
                    <div class="akce-list-section__panel<?= $isActivePanel ? ' is-active' : '' ?>"
                         data-flyer-panel="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') ?>"
                         <?= $isActivePanel ? '' : 'hidden' ?>>
                        <?php foreach ($pages as $pageIndex => $pageItems): ?>
                            <div class="home-flyers__grid akce-list-section__grid<?= $pageIndex === 0 ? ' is-active' : '' ?>"
                                 data-flyer-page="<?= $pageIndex + 1 ?>"
                                 <?= $pageIndex === 0 ? '' : 'hidden' ?>>
                                <?php foreach ($pageItems as $item): ?>
                                    <?php $renderFlyerCard($item); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($pages) > 1): ?>
                            <nav class="news-pagination akce-pagination"
                                 data-flyer-pagination
                                 aria-label="<?= htmlspecialchars(ui_text('flyers.pagination_label', 'Stránkování letáků'), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" data-flyer-page-action="first" aria-label="<?= htmlspecialchars(ui_text('flyers.pagination_first', 'První stránka'), ENT_QUOTES, 'UTF-8') ?>" hidden>‹‹</button>
                                <button type="button" data-flyer-page-action="prev" aria-label="<?= htmlspecialchars(ui_text('flyers.pagination_prev', 'Předchozí stránka'), ENT_QUOTES, 'UTF-8') ?>" hidden>‹</button>
                                <?php foreach ($pages as $pageIndex => $_pageItems): ?>
                                    <button type="button"
                                            class="<?= $pageIndex === 0 ? 'is-active' : '' ?>"
                                            data-flyer-page-button="<?= $pageIndex + 1 ?>"
                                            <?= $pageIndex === 0 ? ' aria-current="page"' : '' ?>
                                            <?= $pageIndex >= 5 ? ' hidden' : '' ?>>
                                        <?= $pageIndex + 1 ?>
                                    </button>
                                <?php endforeach; ?>
                                <button type="button" data-flyer-page-action="next" aria-label="<?= htmlspecialchars(ui_text('flyers.pagination_next', 'Další stránka'), ENT_QUOTES, 'UTF-8') ?>">›</button>
                                <button type="button" data-flyer-page-action="last" aria-label="<?= htmlspecialchars(ui_text('flyers.pagination_last', 'Poslední stránka'), ENT_QUOTES, 'UTF-8') ?>">››</button>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="home-flyers__empty"><?= htmlspecialchars(ui_text($emptyKey, $emptyFallback), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </section>
    <?php
};
?>

<section class="akce-page<?= $offer !== null ? ' akce-page--detail' : '' ?>">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= htmlspecialchars(ui_text('aria.breadcrumb', 'Drobečková navigace'), ENT_QUOTES, 'UTF-8') ?>">
            <ol>
                <li>
                    <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.home', 'Domů'), ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li>
                    <?php if ($offer !== null): ?>
                        <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/akce"><?= htmlspecialchars(ui_text('flyers.title', 'Letáky'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= htmlspecialchars(ui_text('flyers.title', 'Letáky'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </li>
                <?php if ($offer !== null): ?>
                    <li><span aria-current="page"><?= htmlspecialchars((string)$offer['title'], ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php endif; ?>
            </ol>
        </nav>

        <?php if ($offer !== null): ?>
            <?php
            $pages = (array)($offer['pages'] ?? []);
            $pagesJson = json_encode($pages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($pagesJson)) {
                $pagesJson = '[]';
            }
            ?>
            <div class="akce-page__head akce-page__head--detail">
                <a class="akce-page__back" href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/akce"><?= htmlspecialchars(ui_text('akce.back_to_list', 'Zpět na letáky'), ENT_QUOTES, 'UTF-8') ?></a>
                <div>
                    <span><?= htmlspecialchars((string)($offer['type_label'] ?? ui_text('nav.akce', 'Letáky')), ENT_QUOTES, 'UTF-8') ?></span>
                    <h1><?= htmlspecialchars((string)$offer['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if (function_exists('frontend_akce_validity_text')): ?>
                        <?php $validity = frontend_akce_validity_text($offer); ?>
                        <?php if ($validity !== ''): ?><p><?= htmlspecialchars($validity, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="akce-page__head-actions">
                    <?php if ((string)($offer['pdf'] ?? '') !== ''): ?>
                        <a href="<?= htmlspecialchars((string)$offer['pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(ui_text('flyers.download_pdf', 'Stáhnout PDF'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($pages !== []): ?>
                <div class="akce-flip-viewer"
                     data-akce-public-viewer
                     data-pages="<?= htmlspecialchars($pagesJson, ENT_QUOTES, 'UTF-8') ?>"
                     data-close-mode="history"
                     data-close-url="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/akce">
                    <button type="button"
                            class="akce-flip-viewer__close"
                            data-akce-viewer-action="close"
                            aria-label="<?= htmlspecialchars(ui_text('flyers.close_viewer', 'Zavřít prohlížeč'), ENT_QUOTES, 'UTF-8') ?>">
                        ×
                    </button>
                    <div class="akce-flip-viewer__toolbar">
                        <a class="akce-flip-viewer__toolbar-link" href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/akce"><?= htmlspecialchars(ui_text('akce.back_to_list', 'Zpět na letáky'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php if ((string)($offer['pdf'] ?? '') !== ''): ?>
                            <a class="akce-flip-viewer__toolbar-link" href="<?= htmlspecialchars((string)$offer['pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(ui_text('flyers.download_pdf', 'Stáhnout PDF'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                        <button type="button" data-akce-viewer-action="first" aria-label="<?= htmlspecialchars(ui_text('flyers.first_page', 'První strana'), ENT_QUOTES, 'UTF-8') ?>">‹‹</button>
                        <button type="button" data-akce-viewer-action="prev" aria-label="<?= htmlspecialchars(ui_text('flyers.prev_page', 'Předchozí strana'), ENT_QUOTES, 'UTF-8') ?>">‹</button>
                        <span data-akce-viewer-page data-page-word="<?= htmlspecialchars(ui_text('akce.page', 'Strana'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('akce.page', 'Strana'), ENT_QUOTES, 'UTF-8') ?> 1 / <?= count($pages) ?></span>
                        <button type="button" data-akce-viewer-action="next" aria-label="<?= htmlspecialchars(ui_text('flyers.next_page', 'Další strana'), ENT_QUOTES, 'UTF-8') ?>">›</button>
                        <button type="button" data-akce-viewer-action="last" aria-label="<?= htmlspecialchars(ui_text('flyers.last_page', 'Poslední strana'), ENT_QUOTES, 'UTF-8') ?>">››</button>
                        <button type="button" data-akce-viewer-action="zoom-out" aria-label="<?= htmlspecialchars(ui_text('flyers.zoom_out', 'Zmenšit'), ENT_QUOTES, 'UTF-8') ?>">−</button>
                        <button type="button" data-akce-viewer-action="zoom-reset" aria-label="<?= htmlspecialchars(ui_text('flyers.zoom_reset', 'Původní velikost'), ENT_QUOTES, 'UTF-8') ?>">100%</button>
                        <button type="button" data-akce-viewer-action="zoom-in" aria-label="<?= htmlspecialchars(ui_text('flyers.zoom_in', 'Zvětšit'), ENT_QUOTES, 'UTF-8') ?>">+</button>
                        <button type="button" data-akce-viewer-action="fullscreen" aria-label="<?= htmlspecialchars(ui_text('flyers.fullscreen', 'Celá obrazovka'), ENT_QUOTES, 'UTF-8') ?>">⛶</button>
                    </div>
                    <div class="akce-flip-viewer__body">
                        <div class="akce-flip-viewer__book-wrap">
                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--prev" data-akce-viewer-action="prev" aria-label="<?= htmlspecialchars(ui_text('flyers.prev_page', 'Předchozí strana'), ENT_QUOTES, 'UTF-8') ?>">‹</button>
                            <div class="akce-flip-viewer__book-stage" data-akce-viewer-stage>
                                <div class="akce-flip-viewer__book" data-akce-viewer-book></div>
                            </div>
                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--next" data-akce-viewer-action="next" aria-label="<?= htmlspecialchars(ui_text('flyers.next_page', 'Další strana'), ENT_QUOTES, 'UTF-8') ?>">›</button>
                        </div>
                        <div class="akce-flip-viewer__thumbs" data-akce-viewer-thumbs aria-label="<?= htmlspecialchars(ui_text('flyers.page_thumbs', 'Náhledy stran'), ENT_QUOTES, 'UTF-8') ?>"></div>
                    </div>
                    <div class="akce-viewer-simple" data-akce-viewer-fallback>
                        <?php foreach ($pages as $page): ?>
                            <figure class="akce-viewer-simple__page">
                                <img src="<?= htmlspecialchars((string)$page['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$page['label'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                <figcaption><?= htmlspecialchars((string)$page['label'], ENT_QUOTES, 'UTF-8') ?></figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="placeholder-panel">
                    <h1><?= htmlspecialchars(ui_text('common.preparing', 'Připravujeme'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= htmlspecialchars(ui_text('akce.no_pages', 'Pro tento leták zatím nejsou dostupné stránky prohlížeče.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php
            if ($subscribeTypes !== []): ?>
                <div class="akce-top-grid">
                    <div class="akce-top-grid__main">
                        <?php
                        $renderPanelSection(
                            'akce-current',
                            'akce.valid_title',
                            'Právě platné',
                            'akce.valid_text',
                            'Letáky, které jsou aktuálně v platnosti.',
                            (array)($overview['current_panels'] ?? []),
                            'akce.no_current',
                            'Aktuálně zde nejsou žádné platné letáky.',
                            'akce-list-section--current',
                            false
                        );
                        ?>
                    </div>
                    <aside class="akce-top-grid__aside">
                        <?php $renderSubscribeBlock('akce-subscribe--side'); ?>
                    </aside>
                </div>
            <?php else:
                $renderPanelSection(
                    'akce-current',
                    'akce.valid_title',
                    'Právě platné',
                    'akce.valid_text',
                    'Letáky, které jsou aktuálně v platnosti.',
                    (array)($overview['current_panels'] ?? []),
                    'akce.no_current',
                    'Aktuálně zde nejsou žádné platné letáky.',
                    'akce-list-section--current',
                    false
                );
            endif;

            $renderPanelSection(
                'akce-upcoming',
                'akce.upcoming_title',
                'Nadcházející letáky',
                'akce.upcoming_text',
                'Připravované akce, které začnou platit v nejbližších dnech.',
                (array)($overview['upcoming_panels'] ?? []),
                'akce.no_upcoming',
                'Nejsou zde žádné nadcházející letáky.',
                '',
                false
            );

            $renderPanelSection(
                'akce-archive',
                'akce.archive_title',
                'Uplynulé letáky',
                'akce.archive_text',
                'Archiv posledních uplynulých nabídek.',
                (array)($overview['archive_panels'] ?? []),
                'akce.no_archive',
                'Archiv uplynulých letáků je zatím prázdný.'
            );
            ?>
        <?php endif; ?>
    </div>
</section>
