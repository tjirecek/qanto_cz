<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$langPrefix = '/' . rawurlencode($lang);


$routerItems = [
    [
        'title' => ui_text('router.markety.title', 'Markety'),
        'text' => plain_text(stat_vyraz('home.router.markety.text', $lang)),
        'href' => $langPrefix . '/markety',
        'label' => '01',
        'theme' => 'dark',
        'brand' => 'Qanto',
        'logo' => '/img/design/logo_qanto_market_router.png',
        'logo_alt' => 'market Qanto',
    ],
    [
        'title' => ui_text('router.velkoobchod.title', 'Velkoobchody'),
        'text' => plain_text(stat_vyraz('home.router.velkoobchod.text', $lang)),
        'href' => $langPrefix . '/velkoobchod',
        'label' => '02',
        'theme' => 'light',
        'brand' => 'Qanto',
        'logo' => '/img/design/logo_qanto_router.png',
        'logo_alt' => 'Qanto',
    ],
    [
        'title' => ui_text('router.qantoplus.title', 'QantoPlus'),
        'text' => plain_text(stat_vyraz('home.router.qantoplus.text', $lang)),
        'href' => $langPrefix . '/prodejny',
        'label' => '03',
        'theme' => 'cream',
        'brand' => 'Qanto+',
        'logo' => '/img/design/logo_qantoplus_router.png',
        'logo_alt' => 'Qanto+',
    ],
];

$adCarouselItems = function_exists('frontend_banners') ? frontend_banners('main_carousel', $lang, 12) : [];
usort($adCarouselItems, static fn(array $a, array $b): int => ((int)$a['position']) <=> ((int)$b['position']));

$autoSteadyAds = function_exists('frontend_akce_auto_secondary_ads') ? frontend_akce_auto_secondary_ads($lang, 10) : [];
$manualSteadyAds = function_exists('frontend_banners') ? frontend_banners('secondary_links', $lang, 6) : [];
$steadyAds = array_values(array_merge($autoSteadyAds, $manualSteadyAds));

$newsItems = function_exists('frontend_news_latest') ? frontend_news_latest($lang, 4) : [];
$featuredNews = $newsItems[0] ?? null;
$sideNews = array_slice($newsItems, 1, 3);
$flyerCategories = function_exists('frontend_akce_home_flyer_categories') ? frontend_akce_home_flyer_categories($lang, 5) : [];
$allFlyerItems = [];
foreach ($flyerCategories as $category) {
    foreach (($category['items'] ?? []) as $item) {
        $allFlyerItems[] = $item;
    }
}
usort($allFlyerItems, static function (array $a, array $b): int {
    return strcmp((string)($a['date_to'] ?? ''), (string)($b['date_to'] ?? ''))
        ?: strcmp((string)($a['date_from'] ?? ''), (string)($b['date_from'] ?? ''))
        ?: ((int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
});
$allFlyerItems = array_slice($allFlyerItems, 0, 5);
$flyerPanels = $flyerCategories !== []
    ? array_merge([[
        'id' => 'all',
        'label' => ui_text('flyers.all_categories', 'Všechny'),
        'class' => 'home-flyers__tab--all',
        'items' => $allFlyerItems,
    ]], $flyerCategories)
    : [];
?>
<section class="home-router-section" aria-label="<?= htmlspecialchars(ui_text('aria.router', 'Rychlý rozcestník'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="site-shell home-router">
        <?php foreach ($routerItems as $item): ?>
            <?php $themeClass = 'home-router__card--' . preg_replace('~[^a-z0-9_-]+~i', '', (string)$item['theme']); ?>
            <a class="home-router__card <?= htmlspecialchars($themeClass, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="home-router__brand">
                    <?php if (!empty($item['logo'])): ?>
                        <img src="<?= htmlspecialchars((string)$item['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['logo_alt'] ?? $item['brand']), ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    <?php else: ?>
                        <?= htmlspecialchars((string)$item['brand'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <span class="home-router__text">
                    <small><?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?></small>
                    <strong><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <em><?= htmlspecialchars((string)$item['text'], ENT_QUOTES, 'UTF-8') ?></em>
                </span>
                <span class="home-router__arrow" aria-hidden="true">›</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($adCarouselItems !== []): ?>
    <section class="home-section home-section--ads" aria-label="<?= htmlspecialchars(ui_text('aria.ads', 'Aktuální reklamy'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="site-shell">
            <div class="ad-carousel" data-ad-carousel>
                <div class="ad-carousel__viewport" data-ad-carousel-viewport>
                    <div class="ad-carousel__track" data-ad-carousel-track>
                        <?php for ($loop = 0; $loop < 2; $loop++): ?>
                            <?php foreach ($adCarouselItems as $ad): ?>
                                <?php
                                $hasImage = (string)($ad['image'] ?? '') !== '';
                                $hasCoverImage = $hasImage && (string)($ad['image_mode'] ?? '') === 'cover';
                                $textColorClass = $ad['text_color'] === 'light' ? 'ad-card--light' : 'ad-card--dark';
                                $themeClass = 'ad-card--' . preg_replace('~[^a-z0-9_-]+~i', '', $hasCoverImage ? 'custom' : (string)$ad['theme']);
                                $imageClass = $hasCoverImage ? 'ad-card--has-image' : '';
                                $titleLength = function_exists('mb_strlen') ? mb_strlen((string)$ad['title'], 'UTF-8') : strlen((string)$ad['title']);
                                $textDensityClass = 'ad-card--text-short';
                                if ($titleLength > 115) {
                                    $textDensityClass = 'ad-card--text-xlong';
                                } elseif ($titleLength > 78) {
                                    $textDensityClass = 'ad-card--text-long';
                                } elseif ($titleLength > 46) {
                                    $textDensityClass = 'ad-card--text-medium';
                                }
                                ?>
                                <a class="ad-card <?= htmlspecialchars(trim($textColorClass . ' ' . $themeClass . ' ' . $imageClass . ' ' . $textDensityClass), ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string)$ad['href'], ENT_QUOTES, 'UTF-8') ?>" data-valid-from="<?= htmlspecialchars((string)($ad['valid_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-valid-to="<?= htmlspecialchars((string)($ad['valid_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($hasImage): ?>
                                        <img src="<?= htmlspecialchars((string)$ad['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                    <span class="ad-card__content">
                                        <strong><?= htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="ad-card__button"><?= htmlspecialchars((string)$ad['link_text'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="ad-carousel__controls" aria-label="<?= htmlspecialchars(ui_text('aria.ads_controls', 'Ovládání reklam'), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" data-ad-carousel-prev aria-label="<?= htmlspecialchars(ui_text('aria.ads_prev', 'Předchozí reklama'), ENT_QUOTES, 'UTF-8') ?>">‹</button>
                    <button type="button" data-ad-carousel-next aria-label="<?= htmlspecialchars(ui_text('aria.ads_next', 'Další reklama'), ENT_QUOTES, 'UTF-8') ?>">›</button>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($steadyAds !== []): ?>
    <section class="home-section home-section--steady">
        <div class="site-shell">
            <div class="steady-ad-grid">
                <?php foreach ($steadyAds as $ad): ?>
                    <?php
                    $hasImage = (string)($ad['image'] ?? '') !== '';
                    $hasCoverImage = $hasImage && (string)($ad['image_mode'] ?? '') === 'cover';
                    $themeClass = 'steady-ad-card--' . preg_replace('~[^a-z0-9_-]+~i', '', $hasCoverImage ? 'custom' : (string)$ad['theme']);
                    $textColorClass = $ad['text_color'] === 'light' ? 'steady-ad-card--light' : 'steady-ad-card--dark';
                    $imageClass = $hasCoverImage ? 'steady-ad-card--has-image' : '';
                    ?>
                    <a class="steady-ad-card <?= htmlspecialchars(trim($themeClass . ' ' . $textColorClass . ' ' . $imageClass), ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string)$ad['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($hasImage): ?>
                            <img src="<?= htmlspecialchars((string)$ad['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="steady-ad-card__image" loading="lazy">
                        <?php endif; ?>
                        <span class="steady-ad-card__content">
                            <strong><?= htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars((string)$ad['link_text'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="home-section home-news" aria-labelledby="home-news-title">
    <div class="site-shell">
        <div class="home-news__head">
            <div>
                <h2 id="home-news-title"><?= htmlspecialchars(ui_text('news.latest_title', 'Poslední novinky'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(ui_text('news.latest_text', 'Přečtěte si aktuální informace, zprávy, novinky.'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="<?= htmlspecialchars($langPrefix . '/news', ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(ui_text('news.all', 'Všechny novinky'), ENT_QUOTES, 'UTF-8') ?>
                <span aria-hidden="true">›</span>
            </a>
        </div>

        <?php if ($featuredNews): ?>
            <div class="home-news__layout">
                <a class="news-feature" href="<?= htmlspecialchars((string)$featuredNews['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ((string)($featuredNews['image'] ?? '') !== ''): ?>
                        <img src="<?= htmlspecialchars((string)$featuredNews['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="news-feature__body">
                        <time><?= htmlspecialchars((string)$featuredNews['date'], ENT_QUOTES, 'UTF-8') ?></time>
                        <strong><?= htmlspecialchars((string)$featuredNews['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="news-card__text"><?= htmlspecialchars((string)$featuredNews['perex'], ENT_QUOTES, 'UTF-8') ?> <em><?= htmlspecialchars(ui_text('news.read_more', 'přečíst celé'), ENT_QUOTES, 'UTF-8') ?></em></span>
                        <?php if (!empty($featuredNews['tags'])): ?>
                            <span class="news-tags">
                                <?php foreach ($featuredNews['tags'] as $tag): ?>
                                    <span class="news-tag <?= htmlspecialchars((string)$tag['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>

                <div class="news-side-list">
                    <?php foreach ($sideNews as $item): ?>
                        <a class="news-side-card" href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ((string)($item['image'] ?? '') !== ''): ?>
                                <img src="<?= htmlspecialchars((string)$item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <span class="news-side-card__body">
                                <time><?= htmlspecialchars((string)$item['date'], ENT_QUOTES, 'UTF-8') ?></time>
                                <strong><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="news-card__text"><?= htmlspecialchars((string)$item['perex'], ENT_QUOTES, 'UTF-8') ?> <em><?= htmlspecialchars(ui_text('news.read_more', 'přečíst celé'), ENT_QUOTES, 'UTF-8') ?></em></span>
                                <?php if (!empty($item['tags'])): ?>
                                    <span class="news-tags">
                                        <?php foreach ($item['tags'] as $tag): ?>
                                            <span class="news-tag <?= htmlspecialchars((string)$tag['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="home-news__empty"><?= htmlspecialchars(ui_text('news.empty', 'Aktuálně zde nejsou žádné novinky.'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>

<section class="home-section home-flyers" aria-labelledby="home-flyers-title" data-home-flyers>
    <div class="site-shell">
        <div class="home-flyers__head">
            <div>
                <h2 id="home-flyers-title"><?= htmlspecialchars(ui_text('flyers.title', 'Letáky'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(ui_text('flyers.text', 'Prohlédněte si poslední letáky.'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="<?= htmlspecialchars($langPrefix . '/akce', ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(ui_text('flyers.all', 'Všechny letáky'), ENT_QUOTES, 'UTF-8') ?>
                <span aria-hidden="true">›</span>
            </a>
        </div>

        <?php if ($flyerPanels !== []): ?>
            <div class="home-flyers__tabs" role="tablist" aria-label="<?= htmlspecialchars(ui_text('flyers.category', 'Kategorie'), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($flyerPanels as $panelIndex => $panel): ?>
                    <?php
                    $panelId = (string)$panel['id'];
                    $tabId = 'home-flyers-tab-' . preg_replace('~[^a-z0-9_-]+~i', '-', $panelId);
                    ?>
                    <?php $tabClass = trim('home-flyers__tab ' . ($panelIndex === 0 ? 'is-active ' : '') . (string)($panel['class'] ?? '')); ?>
                    <button type="button"
                            id="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') ?>"
                            class="<?= htmlspecialchars($tabClass, ENT_QUOTES, 'UTF-8') ?>"
                            data-flyer-type="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') ?>"
                            role="tab"
                            aria-selected="<?= $panelIndex === 0 ? 'true' : 'false' ?>">
                        <?= htmlspecialchars((string)$panel['label'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="home-flyers__panels">
                <?php foreach ($flyerPanels as $panelIndex => $panel): ?>
                    <?php $panelId = (string)$panel['id']; ?>
                    <div class="home-flyers__grid<?= $panelIndex === 0 ? ' is-active' : '' ?>"
                         data-flyer-panel="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') ?>"
                         <?= $panelIndex === 0 ? '' : 'hidden' ?>>
                        <?php foreach ($panel['items'] as $item): ?>
                            <?php
                            $validityText = sprintf(
                                ui_text('flyers.validity_from_to', 'Platí od %s do %s'),
                                (string)$item['date_from_label'],
                                (string)$item['date_to_label']
                            );
                            ?>
                            <article class="flyer-card">
                                <a class="flyer-card__image" href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
                                    <img src="<?= htmlspecialchars((string)$item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                                </a>
                                <div class="flyer-card__body">
                                    <div class="flyer-card__badges">
                                        <span class="flyer-card__status flyer-card__status--<?= htmlspecialchars((string)$item['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$item['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ((string)($item['type_label'] ?? '') !== ''): ?>
                                            <span class="flyer-card__type <?= htmlspecialchars((string)($item['type_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$item['type_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars($validityText, ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="flyer-card__actions">
                                        <?php if ((string)$item['pdf'] !== ''): ?>
                                            <a href="<?= htmlspecialchars((string)$item['pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(ui_text('flyers.pdf', 'PDF'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <?php endif; ?>
                                        <a href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('flyers.browse', 'Prolistovat'), ENT_QUOTES, 'UTF-8') ?></a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="home-flyers__empty"><?= htmlspecialchars(ui_text('flyers.empty', 'Aktuálně zde nejsou žádné platné ani nadcházející letáky.'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>
