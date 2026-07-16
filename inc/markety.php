<?php
declare(strict_types=1);

$pathParts = array_values(array_filter(explode('/', trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/'))));
$marketSlug = isset($pathParts[2]) ? rawurldecode((string)$pathParts[2]) : '';
$marketSectionDefaults = [
    'route' => 'markety',
    'branch_type' => 'market',
    'flyer_type' => 'markety',
    'title_key' => 'markety.title',
    'title_fallback' => 'Markety',
    'router_title_key' => 'router.markety.title',
    'router_title_fallback' => 'markety',
    'router_text_code' => 'home.router.markety.text',
    'intro_code' => 'markets_intro',
    'text_code' => 'markets_text',
    'router_number' => '01',
    'router_theme' => 'dark',
    'router_logo' => '/img/design/logo_qanto_market_router.png',
    'router_logo_alt' => 'market Qanto',
    'fallback_image' => '/img/design/logo_qanto_router.png',
    'finder_title_key' => 'markety.finder_title',
    'finder_title_fallback' => 'Najděte Qanto market podle města',
    'finder_text_key' => 'markety.finder_text',
    'finder_text_fallback' => 'Zobrazují se všechny dostupné markety.',
    'empty_key' => 'markety.empty',
    'empty_fallback' => 'Aktuálně nejsou dostupné žádné markety.',
    'filter_empty_key' => 'markety.filter_empty',
    'filter_empty_fallback' => 'Pro vybrané město nejsou dostupné žádné markety.',
    'map_empty_key' => 'markety.map_empty',
    'map_empty_fallback' => 'Pro mapu nejsou dostupné souřadnice marketů.',
    'map_branch_key' => 'markety.map_branch',
    'map_branch_fallback' => 'Qanto Market',
    'detail_link_key' => 'markety.detail_link',
    'detail_link_fallback' => 'Detail marketu',
    'detail_not_found_key' => 'markety.detail_not_found',
    'detail_not_found_fallback' => 'Market nebyl nalezen.',
];
$marketSectionConfig = array_replace($marketSectionDefaults, is_array($marketSectionConfig ?? null) ? $marketSectionConfig : []);
$marketRoute = (string)$marketSectionConfig['route'];
$marketBranchType = (string)$marketSectionConfig['branch_type'];
$marketFlyerType = (string)$marketSectionConfig['flyer_type'];
$marketFallbackImage = (string)$marketSectionConfig['fallback_image'];
$marketRouterThemeClass = 'home-router__card--' . preg_replace('~[^a-z0-9_-]+~i', '', (string)$marketSectionConfig['router_theme']);

if ($marketSlug !== ''):
    $marketDetail = function_exists('frontend_markety_detail_by_slug') ? frontend_markety_detail_by_slug($marketSlug, $lang, $marketBranchType) : null;
    if ($marketDetail === null):
        http_response_code(404);
        ?>
        <section class="markets-page markets-detail-page">
            <div class="site-shell">
                <nav class="site-breadcrumb" aria-label="<?= frontend_markety_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
                    <ol>
                        <li><a href="/<?= frontend_markety_e($lang) ?>" aria-label="<?= frontend_markety_e(ui_text('aria.home', 'Domů')) ?>"><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg></a></li>
                        <li><a href="/<?= frontend_markety_e($lang) ?>/<?= frontend_markety_e($marketRoute) ?>"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['title_key'], (string)$marketSectionConfig['title_fallback'])) ?></a></li>
                        <li><span aria-current="page"><?= frontend_markety_e(ui_text('common.page_not_found', 'Stránka nebyla nalezena.')) ?></span></li>
                    </ol>
                </nav>
                <div class="alert alert-warning"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['detail_not_found_key'], (string)$marketSectionConfig['detail_not_found_fallback'])) ?></div>
            </div>
        </section>
        <?php
        return;
    endif;

    $marketMapPoints = function_exists('frontend_markety_map_points') ? frontend_markety_map_points([$marketDetail]) : [];
    $marketFlyers = function_exists('frontend_markety_flyers') ? frontend_markety_flyers($lang, 3, $marketFlyerType) : [];
    $marketJobs = function_exists('frontend_markety_jobs') ? frontend_markety_jobs((int)$marketDetail['stredisko'], $lang, 8) : [];
    $marketPhotos = function_exists('frontend_markety_gallery_photos') ? frontend_markety_gallery_photos((int)$marketDetail['gallery_id'], $lang, 48) : [];
    $marketHasIntroImage = (string)$marketDetail['image'] !== '';
    $marketImage = (string)($marketHasIntroImage ? $marketDetail['image'] : $marketFallbackImage);
    $contactFormCategories = function_exists('frontend_contacts_form_categories') ? frontend_contacts_form_categories($lang) : [];
    $contactFormResult = null;

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (string)($_POST['action'] ?? '') === 'contacts_form'
        && function_exists('frontend_contacts_form_save')
    ) {
        $contactFormResult = frontend_contacts_form_save($_POST);
    }

    $contactFormToken = function_exists('frontend_contacts_form_token') ? frontend_contacts_form_token() : '';
    $keepContactFormValues = is_array($contactFormResult) && !(bool)($contactFormResult['ok'] ?? false);
    $contactFormValue = static function (string $key) use ($keepContactFormValues): string {
        return $keepContactFormValues ? (string)($_POST[$key] ?? '') : '';
    };
    $selectedContactCategory = $keepContactFormValues ? (int)($_POST['category_id'] ?? 0) : 0;
    ?>
    <section class="markets-page markets-detail-page">
        <div class="site-shell">
            <nav class="site-breadcrumb" aria-label="<?= frontend_markety_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
                <ol>
                    <li>
                        <a href="/<?= frontend_markety_e($lang) ?>" aria-label="<?= frontend_markety_e(ui_text('aria.home', 'Domů')) ?>">
                            <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                        </a>
                    </li>
                    <li><a href="/<?= frontend_markety_e($lang) ?>/<?= frontend_markety_e($marketRoute) ?>"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['title_key'], (string)$marketSectionConfig['title_fallback'])) ?></a></li>
                    <li><span aria-current="page"><?= frontend_markety_e((string)$marketDetail['name']) ?></span></li>
                </ol>
            </nav>

            <header class="market-detail-router">
                <div class="home-router__card <?= frontend_markety_e($marketRouterThemeClass) ?> markets-page-router market-detail-router__card">
                    <span class="home-router__brand">
                        <img src="<?= frontend_markety_e((string)$marketSectionConfig['router_logo']) ?>" alt="<?= frontend_markety_e((string)$marketSectionConfig['router_logo_alt']) ?>" loading="lazy">
                    </span>
                    <span class="home-router__text">
                        <small><?= frontend_markety_e((string)$marketSectionConfig['router_number']) ?></small>
                        <h1><?= frontend_markety_e((string)$marketDetail['name']) ?></h1>
                        <?php if ((string)$marketDetail['address'] !== ''): ?><em><?= frontend_markety_e((string)$marketDetail['address']) ?></em><?php endif; ?>
                    </span>
                </div>
            </header>

            <div class="market-detail__hero">
                <figure class="market-detail__photo<?= $marketHasIntroImage ? '' : ' is-logo-fallback' ?>">
                    <img src="<?= frontend_markety_e($marketImage) ?>" alt="<?= frontend_markety_e((string)$marketDetail['name']) ?>" loading="eager">
                    <span><?= frontend_markety_e($marketPhotos !== [] ? sprintf(ui_text('markety.gallery_count', '%d fotek'), count($marketPhotos)) : ui_text('markety.gallery_placeholder', 'Fotogalerie připravujeme')) ?></span>
                </figure>
                <div
                    class="markets-map market-detail__map"
                    data-markets-map
                    data-points="<?= frontend_markety_e(json_encode($marketMapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>"
                    data-empty="<?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_empty_key'], (string)$marketSectionConfig['map_empty_fallback'])) ?>"
                    data-label-all-cities="<?= frontend_markety_e(ui_text('markety.all_cities', 'Celá ČR')) ?>"
                    data-label-branch="<?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_branch_key'], (string)$marketSectionConfig['map_branch_fallback'])) ?>"
                >
                    <div class="markets-map__empty"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_empty_key'], (string)$marketSectionConfig['map_empty_fallback'])) ?></div>
                </div>
            </div>

            <div class="market-detail__info-grid">
                <section class="market-detail-card market-detail-opening" aria-labelledby="market-opening-title">
                    <div class="market-detail-opening__head">
                        <h2 id="market-opening-title"><?= frontend_markety_e(ui_text('markety.opening_title', 'Otevírací doba')) ?></h2>
                        <span class="market-detail-opening__status <?= !empty($marketDetail['is_open']) ? 'is-open' : 'is-closed' ?>">
                            <?= frontend_markety_e(!empty($marketDetail['is_open']) ? ui_text('markety.open_now', 'Otevřeno') : ui_text('markety.closed_now', 'Zavřeno')) ?>
                        </span>
                    </div>
                    <?php if (!empty($marketDetail['opening_has_exception_today'])): ?>
                        <p class="market-detail-opening__exception">
                            <?= frontend_markety_e(ui_text('markety.opening_exception_today', 'Dnes platí upravená otevírací doba.')) ?>
                        </p>
                    <?php endif; ?>
                    <dl>
                        <?php foreach ((array)$marketDetail['opening_week'] as $row): ?>
                            <?php
                            $openingRowClasses = array_filter([
                                !empty($row['closed']) ? 'is-closed' : '',
                                !empty($row['is_today']) ? 'is-today' : '',
                                !empty($row['is_open']) ? 'is-open' : '',
                                !empty($row['is_exception']) ? 'is-exception' : '',
                            ]);
                            ?>
                            <div class="<?= frontend_markety_e(implode(' ', $openingRowClasses)) ?>">
                                <dt>
                                    <span><?= frontend_markety_e((string)$row['label']) ?></span>
                                    <?php if (!empty($row['is_today'])): ?><em><?= frontend_markety_e(ui_text('markety.today', 'Dnes')) ?></em><?php endif; ?>
                                    <?php if (!empty($row['is_exception'])): ?><small><?= frontend_markety_e(ui_text('markety.exception', 'Výjimka')) ?></small><?php endif; ?>
                                    <?php if ((string)$row['note'] !== ''): ?><small><?= frontend_markety_e((string)$row['note']) ?></small><?php endif; ?>
                                </dt>
                                <dd>
                                    <?= frontend_markety_e((string)$row['time']) ?>
                                    <?php if (!empty($row['is_today'])): ?>
                                        <small><?= frontend_markety_e(!empty($row['is_open']) ? ui_text('markety.open_now', 'Otevřeno') : ui_text('markety.closed_now', 'Zavřeno')) ?></small>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <section class="market-detail-card market-detail-services" aria-labelledby="market-services-title">
                    <h2 id="market-services-title"><?= frontend_markety_e(ui_text('markety.services_title', 'Služby')) ?></h2>
                    <?php if ($marketDetail['services'] !== []): ?>
                        <ul>
                            <?php foreach ((array)$marketDetail['services'] as $service): ?>
                                <li><span aria-hidden="true">+</span><?= frontend_markety_e((string)$service) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?= frontend_markety_e(ui_text('markety.services_empty', 'Služby pro tuto prodejnu doplníme.')) ?></p>
                    <?php endif; ?>
                </section>

                <section class="market-detail-card market-detail-contact" aria-labelledby="market-contact-title">
                    <span class="market-detail-contact__icon" aria-hidden="true">↗</span>
                    <h2 id="market-contact-title"><?= frontend_markety_e(ui_text('markety.store_contact_title', 'Kontakt na prodejnu')) ?></h2>
                    <?php if ((string)$marketDetail['address'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">⌖</span><span><?= frontend_markety_e((string)$marketDetail['address']) ?></span></p><?php endif; ?>
                    <?php if ((string)$marketDetail['email'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">✉</span><span><a href="mailto:<?= frontend_markety_e((string)$marketDetail['email']) ?>"><?= frontend_markety_e((string)$marketDetail['email']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$marketDetail['phone'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">☎</span><span><a href="tel:<?= frontend_markety_e(preg_replace('~\s+~', '', (string)$marketDetail['phone']) ?? '') ?>"><?= frontend_markety_e((string)$marketDetail['phone']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$marketDetail['manager'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">•</span><span><?= frontend_markety_e(ui_text('markety.manager', 'Vedoucí')) ?>: <?= frontend_markety_e((string)$marketDetail['manager']) ?></span></p><?php endif; ?>
                </section>
            </div>

            <section class="market-detail-section market-detail-jobs" aria-labelledby="market-jobs-title">
                <h2 id="market-jobs-title"><?= frontend_markety_e(ui_text('markety.jobs_title', 'Volná místa na prodejně')) ?> <span>(<?= count($marketJobs) ?>)</span></h2>
                <?php if ($marketJobs !== []): ?>
                    <div class="market-detail-jobs__list">
                        <?php foreach ($marketJobs as $job): ?>
                            <a href="<?= frontend_markety_e((string)$job['url']) ?>">
                                <span><strong><?= frontend_markety_e((string)$job['title']) ?></strong><small><?= frontend_markety_e((string)$marketDetail['city']) ?><?= (string)$job['location'] !== '' ? ' - ' . frontend_markety_e((string)$job['location']) : '' ?></small></span>
                                <span aria-hidden="true">›</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_markety_e(ui_text('markety.jobs_empty', 'Na této prodejně teď nejsou vypsaná volná místa.')) ?></p>
                <?php endif; ?>
            </section>

            <section class="market-detail-section market-detail-flyers" aria-labelledby="market-flyers-title">
                <div class="market-detail-section__head">
                    <div>
                        <h2 id="market-flyers-title"><?= frontend_markety_e(ui_text('markety.flyers_title', 'Letáky')) ?></h2>
                    </div>
                    <a class="market-detail-section__link" href="/<?= frontend_markety_e($lang) ?>/akce?typ=<?= frontend_markety_e($marketFlyerType) ?>">
                        <?= frontend_markety_e(ui_text('flyers.all', 'Všechny letáky')) ?>
                    </a>
                </div>
                <?php if ($marketFlyers !== []): ?>
                    <div class="market-detail-flyers__grid">
                        <?php foreach ($marketFlyers as $flyer): ?>
                            <?php
                            $flyerPages = array_values((array)($flyer['pages'] ?? []));
                            $flyerViewerId = 'market-flyer-viewer-' . (int)$flyer['id'];
                            $flyerPagesJson = json_encode($flyerPages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            if (!is_string($flyerPagesJson)) {
                                $flyerPagesJson = '[]';
                            }
                            ?>
                            <article class="market-detail-flyer">
                                <a class="market-detail-flyer__image" href="<?= frontend_markety_e((string)$flyer['href']) ?>">
                                    <?php if ((string)$flyer['image'] !== ''): ?>
                                        <img src="<?= frontend_markety_e((string)$flyer['image']) ?>" alt="<?= frontend_markety_e((string)$flyer['title']) ?>" loading="lazy">
                                    <?php endif; ?>
                                </a>
                                <strong><?= frontend_markety_e((string)$flyer['title']) ?></strong>
                                <p><?= frontend_markety_e(function_exists('frontend_akce_validity_text') ? frontend_akce_validity_text($flyer) : '') ?></p>
                                <div>
                                    <?php if ((string)$flyer['pdf'] !== ''): ?><a href="<?= frontend_markety_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener"><?= frontend_markety_e(ui_text('flyers.pdf', 'PDF')) ?></a><?php endif; ?>
                                    <?php if ($flyerPages !== []): ?>
                                        <a href="<?= frontend_markety_e((string)$flyer['href']) ?>" data-akce-viewer-open="<?= frontend_markety_e($flyerViewerId) ?>"><?= frontend_markety_e(ui_text('flyers.browse', 'Prolistovat')) ?></a>
                                    <?php else: ?>
                                        <a href="<?= frontend_markety_e((string)$flyer['href']) ?>"><?= frontend_markety_e(ui_text('flyers.browse', 'Prolistovat')) ?></a>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php if ($flyerPages !== []): ?>
                                <div class="akce-flip-viewer akce-flip-viewer--modal"
                                     id="<?= frontend_markety_e($flyerViewerId) ?>"
                                     data-akce-public-viewer
                                     data-akce-viewer-modal
                                     data-pages="<?= frontend_markety_e($flyerPagesJson) ?>"
                                     aria-modal="true"
                                     role="dialog"
                                     aria-label="<?= frontend_markety_e((string)$flyer['title']) ?>">
                                    <button type="button"
                                            class="akce-flip-viewer__close"
                                            data-akce-viewer-action="close"
                                            aria-label="<?= frontend_markety_e(ui_text('flyers.close_viewer', 'Zavřít prohlížeč')) ?>">
                                        ×
                                    </button>
                                    <div class="akce-flip-viewer__toolbar">
                                        <?php if ((string)$flyer['pdf'] !== ''): ?>
                                            <a class="akce-flip-viewer__toolbar-link" href="<?= frontend_markety_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener"><?= frontend_markety_e(ui_text('flyers.download_pdf', 'Stáhnout PDF')) ?></a>
                                        <?php endif; ?>
                                        <button type="button" data-akce-viewer-action="first" aria-label="<?= frontend_markety_e(ui_text('flyers.first_page', 'První strana')) ?>">‹‹</button>
                                        <button type="button" data-akce-viewer-action="prev" aria-label="<?= frontend_markety_e(ui_text('flyers.prev_page', 'Předchozí strana')) ?>">‹</button>
                                        <span data-akce-viewer-page data-page-word="<?= frontend_markety_e(ui_text('akce.page', 'Strana')) ?>"><?= frontend_markety_e(ui_text('akce.page', 'Strana')) ?> 1 / <?= count($flyerPages) ?></span>
                                        <button type="button" data-akce-viewer-action="next" aria-label="<?= frontend_markety_e(ui_text('flyers.next_page', 'Další strana')) ?>">›</button>
                                        <button type="button" data-akce-viewer-action="last" aria-label="<?= frontend_markety_e(ui_text('flyers.last_page', 'Poslední strana')) ?>">››</button>
                                        <button type="button" data-akce-viewer-action="zoom-out" aria-label="<?= frontend_markety_e(ui_text('flyers.zoom_out', 'Zmenšit')) ?>">−</button>
                                        <button type="button" data-akce-viewer-action="zoom-reset" aria-label="<?= frontend_markety_e(ui_text('flyers.zoom_reset', 'Původní velikost')) ?>">100%</button>
                                        <button type="button" data-akce-viewer-action="zoom-in" aria-label="<?= frontend_markety_e(ui_text('flyers.zoom_in', 'Zvětšit')) ?>">+</button>
                                        <button type="button" data-akce-viewer-action="fullscreen" aria-label="<?= frontend_markety_e(ui_text('flyers.fullscreen', 'Celá obrazovka')) ?>">⛶</button>
                                    </div>
                                    <div class="akce-flip-viewer__body">
                                        <div class="akce-flip-viewer__book-wrap">
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--prev" data-akce-viewer-action="prev" aria-label="<?= frontend_markety_e(ui_text('flyers.prev_page', 'Předchozí strana')) ?>">‹</button>
                                            <div class="akce-flip-viewer__book-stage" data-akce-viewer-stage>
                                                <div class="akce-flip-viewer__book" data-akce-viewer-book></div>
                                            </div>
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--next" data-akce-viewer-action="next" aria-label="<?= frontend_markety_e(ui_text('flyers.next_page', 'Další strana')) ?>">›</button>
                                        </div>
                                        <div class="akce-flip-viewer__thumbs" data-akce-viewer-thumbs aria-label="<?= frontend_markety_e(ui_text('flyers.page_thumbs', 'Náhledy stran')) ?>"></div>
                                    </div>
                                    <div class="akce-viewer-simple" data-akce-viewer-fallback>
                                        <?php foreach ($flyerPages as $page): ?>
                                            <figure class="akce-viewer-simple__page">
                                                <img src="<?= frontend_markety_e((string)($page['src'] ?? '')) ?>" alt="<?= frontend_markety_e((string)($page['label'] ?? '')) ?>" loading="lazy">
                                                <figcaption><?= frontend_markety_e((string)($page['label'] ?? '')) ?></figcaption>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_markety_e(ui_text('akce.no_current', 'Aktuálně zde nejsou žádné platné letáky.')) ?></p>
                <?php endif; ?>
            </section>

            <?php if ($marketPhotos !== []): ?>
                <section
                    class="market-detail-section market-detail-gallery"
                    aria-labelledby="market-gallery-title"
                    data-market-gallery
                >
                    <div class="market-detail-section__head">
                        <div>
                            <h2 id="market-gallery-title"><?= frontend_markety_e(ui_text('markety.gallery_title', 'Fotogalerie')) ?></h2>
                            <p><?= frontend_markety_e(sprintf(ui_text('markety.gallery_intro', 'Prohlédněte si %d fotografií této prodejny.'), count($marketPhotos))) ?></p>
                        </div>
                    </div>
                    <div class="market-detail-gallery__grid">
                        <?php foreach ($marketPhotos as $index => $photo): ?>
                            <button
                                type="button"
                                class="market-detail-gallery__item"
                                data-market-gallery-item
                                data-market-gallery-index="<?= (int)$index ?>"
                                data-full="<?= frontend_markety_e((string)$photo['image']) ?>"
                                data-title="<?= frontend_markety_e((string)($photo['title'] !== '' ? $photo['title'] : $marketDetail['name'])) ?>"
                                aria-label="<?= frontend_markety_e(sprintf(ui_text('markety.gallery_open_photo', 'Otevřít fotografii %d'), $index + 1)) ?>"
                            >
                                <img src="<?= frontend_markety_e((string)$photo['thumb']) ?>" alt="<?= frontend_markety_e((string)($photo['title'] !== '' ? $photo['title'] : $marketDetail['name'])) ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div
                        class="market-gallery-lightbox"
                        data-market-gallery-lightbox
                        data-label-close="<?= frontend_markety_e(ui_text('common.close', 'Zavřít')) ?>"
                        data-label-prev="<?= frontend_markety_e(ui_text('common.previous', 'Předchozí')) ?>"
                        data-label-next="<?= frontend_markety_e(ui_text('common.next', 'Další')) ?>"
                        hidden
                    ></div>
                </section>
            <?php endif; ?>

            <?php include __DIR__ . '/partials/contact_form.php'; ?>
        </div>
    </section>
    <?php
    return;
endif;

$marketBranches = function_exists('frontend_markety_list') ? frontend_markety_list($lang, $marketBranchType) : [];
$marketMapPoints = function_exists('frontend_markety_map_points') ? frontend_markety_map_points($marketBranches) : [];
$marketIntro = function_exists('stat_text') ? trim((string)(stat_text((string)$marketSectionConfig['intro_code'], $lang) ?? '')) : '';
$marketText = function_exists('stat_text') ? trim((string)(stat_text((string)$marketSectionConfig['text_code'], $lang) ?? '')) : '';
$marketRouterText = (function_exists('stat_vyraz') && function_exists('plain_text')) ? plain_text(stat_vyraz((string)$marketSectionConfig['router_text_code'], $lang)) : '';
?>
<section class="markets-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= frontend_markety_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
            <ol>
                <li>
                    <a href="/<?= frontend_markety_e($lang) ?>" aria-label="<?= frontend_markety_e(ui_text('aria.home', 'Domů')) ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li><span aria-current="page"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['title_key'], (string)$marketSectionConfig['title_fallback'])) ?></span></li>
            </ol>
        </nav>

        <header class="markets-page__head">
            <div class="home-router__card <?= frontend_markety_e($marketRouterThemeClass) ?> markets-page-router">
                <span class="home-router__brand">
                    <img src="<?= frontend_markety_e((string)$marketSectionConfig['router_logo']) ?>" alt="<?= frontend_markety_e((string)$marketSectionConfig['router_logo_alt']) ?>" loading="lazy">
                </span>
                <span class="home-router__text">
                    <small><?= frontend_markety_e((string)$marketSectionConfig['router_number']) ?></small>
                    <h1><a href="/<?= frontend_markety_e($lang) ?>/<?= frontend_markety_e($marketRoute) ?>"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['router_title_key'], (string)$marketSectionConfig['router_title_fallback'])) ?></a></h1>
                    <?php if ($marketRouterText !== ''): ?><em><?= frontend_markety_e($marketRouterText) ?></em><?php endif; ?>
                </span>
            </div>
            <div class="markets-page__intro"><?= $marketIntro ?></div>
        </header>

        <section class="markets-finder" aria-label="<?= frontend_markety_e(ui_text((string)$marketSectionConfig['finder_title_key'], (string)$marketSectionConfig['finder_title_fallback'])) ?>">
            <div class="markets-finder__sidebar">
                <div class="markets-finder__header">
                    <div>
                        <h2><?= frontend_markety_e(ui_text((string)$marketSectionConfig['finder_title_key'], (string)$marketSectionConfig['finder_title_fallback'])) ?></h2>
                        <p><?= frontend_markety_e(ui_text((string)$marketSectionConfig['finder_text_key'], (string)$marketSectionConfig['finder_text_fallback'])) ?></p>
                    </div>
                    <div class="markets-city" data-markets-city-picker>
                        <button
                            type="button"
                            class="markets-city__trigger"
                            data-markets-city-trigger
                            aria-expanded="false"
                        >
                            <span data-markets-city-label><?= frontend_markety_e(ui_text('markety.all_cities', 'Celá ČR')) ?></span>
                        </button>
                        <div
                            class="markets-city__panel"
                            data-markets-city-panel
                            data-search-placeholder="<?= frontend_markety_e(ui_text('markety.city_search_placeholder', 'Hledat obec')) ?>"
                            data-search-empty="<?= frontend_markety_e(ui_text('markety.city_search_empty', 'Žádná obec neodpovídá hledání.')) ?>"
                            hidden
                        ></div>
                    </div>
                    <button
                        type="button"
                        class="markets-mobile-toggle"
                        data-markets-mobile-toggle
                        data-label-map="<?= frontend_markety_e(ui_text('markety.show_map', 'Zobrazit mapu')) ?>"
                        data-label-list="<?= frontend_markety_e(ui_text('markety.show_list', 'Zobrazit seznam')) ?>"
                        aria-pressed="false"
                    >
                        <span data-markets-mobile-toggle-label><?= frontend_markety_e(ui_text('markety.show_map', 'Zobrazit mapu')) ?></span>
                    </button>
                </div>

                <div class="markets-list">
                    <?php if ($marketBranches === []): ?>
                        <div class="markets-list__empty"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['empty_key'], (string)$marketSectionConfig['empty_fallback'])) ?></div>
                    <?php else: ?>
                        <?php foreach ($marketBranches as $branch): ?>
                            <article
                                class="markets-card"
                                id="market-<?= (int)$branch['id'] ?>"
                                data-market-card
                                data-market-id="<?= (int)$branch['id'] ?>"
                                data-city="<?= frontend_markety_e((string)$branch['city']) ?>"
                            >
                                <button type="button" class="markets-card__button" data-market-focus="<?= (int)$branch['id'] ?>">
                                    <span class="markets-card__image is-logo-fallback">
                                        <img src="/img/design/logo_qanto_router.png" alt="<?= frontend_markety_e((string)$branch['name']) ?>" loading="lazy">
                                    </span>
                                    <span class="markets-card__body">
                                        <strong><?= frontend_markety_e((string)$branch['name']) ?></strong>
                                        <?php if ((string)$branch['address'] !== ''): ?><span><?= frontend_markety_e((string)$branch['address']) ?></span><?php endif; ?>
                                        <small class="<?= $branch['is_open'] ? 'is-open' : '' ?>"><?= frontend_markety_e((string)$branch['opening_label']) ?></small>
                                    </span>
                                </button>
                                <a class="markets-card__detail" href="<?= frontend_markety_e(frontend_markety_detail_url($branch, $lang, $marketRoute)) ?>"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['detail_link_key'], (string)$marketSectionConfig['detail_link_fallback'])) ?></a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="markets-list__empty" data-markets-empty hidden><?= frontend_markety_e(ui_text((string)$marketSectionConfig['filter_empty_key'], (string)$marketSectionConfig['filter_empty_fallback'])) ?></div>
                </div>
            </div>

            <div
                class="markets-map"
                data-markets-map
                data-points="<?= frontend_markety_e(json_encode($marketMapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>"
                data-empty="<?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_empty_key'], (string)$marketSectionConfig['map_empty_fallback'])) ?>"
                data-label-all-cities="<?= frontend_markety_e(ui_text('markety.all_cities', 'Celá ČR')) ?>"
                data-label-branch="<?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_branch_key'], (string)$marketSectionConfig['map_branch_fallback'])) ?>"
            >
                <div class="markets-map__empty"><?= frontend_markety_e(ui_text((string)$marketSectionConfig['map_empty_key'], (string)$marketSectionConfig['map_empty_fallback'])) ?></div>
            </div>
        </section>

        <?php if ($marketText !== ''): ?>
            <section class="markets-text">
                <?= $marketText ?>
            </section>
        <?php endif; ?>

        <section class="markets-contact">
            <div class="markets-contact__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3a8 8 0 0 0-8 8v3.2A2.8 2.8 0 0 0 6.8 17H8v-6H5.8A6.2 6.2 0 0 1 18.2 11H16v6h1.6a4.6 4.6 0 0 1-4.3 3H11v-2h2.3a2.6 2.6 0 0 0 2.4-1.6A2.8 2.8 0 0 0 20 14.2V11a8 8 0 0 0-8-8Z"/></svg>
            </div>
            <div>
                <h2><?= frontend_markety_e(ui_text('markety.contact_title', 'Kontaktujte nás')) ?></h2>
                <p><?= frontend_markety_e(ui_text('markety.contact_text', 'Spojte se s námi, ať už potřebujete cokoliv vyřídit nebo zanechat připomínku.')) ?></p>
            </div>
            <a href="/<?= frontend_markety_e($lang) ?>/kontakty">
                <?= frontend_markety_e(ui_text('markety.contact_button', 'Zobrazit kontakty')) ?>
                <span aria-hidden="true">›</span>
            </a>
        </section>
    </div>
</section>
