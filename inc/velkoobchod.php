<?php
declare(strict_types=1);

$pathParts = array_values(array_filter(explode('/', trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/'))));
$wholesaleSlug = isset($pathParts[2]) ? rawurldecode((string)$pathParts[2]) : '';
$renderWholesaleRepresentativeCard = static function (array $person, bool $filterable = false): void {
    $branchId = (int)($person['branch_id'] ?? 0);
    $name = trim((string)($person['name'] ?? ''));
    $branchLabel = trim((string)($person['branch_label'] ?? ''));
    $description = trim((string)($person['description'] ?? ''));
    $email = trim((string)($person['email'] ?? ''));
    $phone = trim((string)($person['phone'] ?? ''));
    $phoneHref = preg_replace('~\s+~', '', $phone) ?? '';
    ?>
    <article class="wholesale-rep-card"<?php if ($filterable): ?> data-wholesale-representative data-branch-id="<?= $branchId ?>"<?php endif; ?>>
        <?php if ((string)($person['image'] ?? '') !== ''): ?>
            <img class="wholesale-rep-card__photo" src="<?= frontend_velkoobchod_e((string)$person['image']) ?>" alt="<?= frontend_velkoobchod_e($name) ?>" loading="lazy">
        <?php else: ?>
            <span class="wholesale-rep-card__photo wholesale-rep-card__initial" aria-hidden="true"><?= frontend_velkoobchod_e(mb_substr($name, 0, 1, 'UTF-8')) ?></span>
        <?php endif; ?>
        <div class="wholesale-rep-card__body">
            <h4><?= frontend_velkoobchod_e($name) ?></h4>
            <?php if ($description !== ''): ?><div class="wholesale-rep-card__description"><?= $description ?></div><?php endif; ?>
            <div class="wholesale-rep-card__contacts">
                <?php if ($branchLabel !== ''): ?>
                    <span class="wholesale-rep-card__row">
                        <svg class="wholesale-rep-card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.5A7.2 7.2 0 0 0 4.8 9.7c0 4.9 6.1 11.2 6.4 11.5l.8.8.8-.8c.3-.3 6.4-6.6 6.4-11.5A7.2 7.2 0 0 0 12 2.5Zm0 15.9c-1.9-2.1-4.8-6-4.8-8.7a4.8 4.8 0 1 1 9.6 0c0 2.7-2.9 6.6-4.8 8.7Zm0-11.8a3.1 3.1 0 1 0 0 6.2 3.1 3.1 0 0 0 0-6.2Zm0 4a.9.9 0 1 1 0-1.8.9.9 0 0 1 0 1.8Z"/></svg>
                        <span class="wholesale-rep-card__branch"><?= frontend_velkoobchod_e($branchLabel) ?></span>
                    </span>
                <?php endif; ?>
                <?php if ($phone !== ''): ?>
                    <a class="wholesale-rep-card__row" href="tel:<?= frontend_velkoobchod_e($phoneHref) ?>">
                        <svg class="wholesale-rep-card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.1 3.1 10 8.8 7.8 11c1.1 2.2 3 4.1 5.2 5.2l2.2-2.2 5.7 2.9-.9 4.5-1 .2c-.7.1-1.4.2-2 .2C8.9 21.8 2.2 15.1 2.2 7c0-.7.1-1.4.2-2l.2-1 4.5-.9Zm-.9 2.5-1.5.3v1.2c0 6.9 5.5 12.4 12.3 12.4h1.1l.3-1.5-2.7-1.4-2.2 2.2-.7-.3A13.2 13.2 0 0 1 5.5 11l-.3-.7 2.2-2.2-1.2-2.5Z"/></svg>
                        <span><?= frontend_velkoobchod_e($phone) ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <a class="wholesale-rep-card__row" href="mailto:<?= frontend_velkoobchod_e($email) ?>">
                        <svg class="wholesale-rep-card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h18v14H3V5Zm2.4 2 6.6 5.1L18.6 7H5.4Zm13.2 10V9.6L12 14.7 5.4 9.6V17h13.2Z"/></svg>
                        <span><?= frontend_velkoobchod_e($email) ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
};

if ($wholesaleSlug !== ''):
    $wholesaleDetail = function_exists('frontend_markety_detail_by_slug') ? frontend_markety_detail_by_slug($wholesaleSlug, $lang, 'velkoobchod') : null;
    if ($wholesaleDetail === null):
        http_response_code(404);
        ?>
        <section class="markets-page markets-detail-page wholesale-page wholesale-detail-page">
            <div class="site-shell">
                <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb')) ?>">
                    <ol>
                        <li>
                            <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb_home')) ?>">
                                <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                            </a>
                        </li>
                        <li><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title')) ?></a></li>
                        <li><span aria-current="page"><?= frontend_velkoobchod_e(ui_text('common.page_not_found')) ?></span></li>
                    </ol>
                </nav>
                <div class="alert alert-warning"><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_not_found')) ?></div>
            </div>
        </section>
        <?php
        return;
    endif;

    $wholesaleMapPoints = function_exists('frontend_markety_map_points') ? frontend_markety_map_points([$wholesaleDetail]) : [];
    $wholesaleRepresentatives = function_exists('frontend_velkoobchod_representatives') ? frontend_velkoobchod_representatives($lang) : [];
    $wholesaleRepresentatives = array_values(array_filter($wholesaleRepresentatives, static function (array $person) use ($wholesaleDetail): bool {
        return (int)($person['branch_id'] ?? 0) === (int)$wholesaleDetail['id'];
    }));
    $wholesaleJobs = function_exists('frontend_markety_jobs') ? frontend_markety_jobs((int)$wholesaleDetail['stredisko'], $lang, 8) : [];
    $wholesaleFlyerType = 'velkoobchod';
    $wholesaleFlyers = function_exists('frontend_markety_flyers') ? frontend_markety_flyers($lang, 3, $wholesaleFlyerType) : [];
    $wholesalePhotos = function_exists('frontend_markety_gallery_photos') ? frontend_markety_gallery_photos((int)$wholesaleDetail['gallery_id'], $lang, 48) : [];
    $wholesaleLogoImage = '/img/design/logo_qanto_router.png';
    $wholesaleHasIntroImage = (string)$wholesaleDetail['image'] !== '';
    $wholesaleImage = $wholesaleHasIntroImage ? (string)$wholesaleDetail['image'] : $wholesaleLogoImage;
    $wholesaleUsesLogoImage = $wholesaleImage === $wholesaleLogoImage;
    ?>
    <section class="markets-page markets-detail-page wholesale-page wholesale-detail-page">
        <div class="site-shell">
            <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb')) ?>">
                <ol>
                    <li>
                        <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb_home')) ?>">
                            <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                        </a>
                    </li>
                    <li><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title')) ?></a></li>
                    <li><span aria-current="page"><?= frontend_velkoobchod_e((string)$wholesaleDetail['name']) ?></span></li>
                </ol>
            </nav>

            <header class="market-detail-router">
                <div class="home-router__card home-router__card--light markets-page-router market-detail-router__card">
                    <span class="home-router__brand">
                        <img src="/img/design/logo_qanto_router.png" alt="Qanto" loading="lazy">
                    </span>
                    <span class="home-router__text">
                        <small>02</small>
                        <h1><?= frontend_velkoobchod_e((string)$wholesaleDetail['name']) ?></h1>
                        <?php if ((string)$wholesaleDetail['address'] !== ''): ?><em><?= frontend_velkoobchod_e((string)$wholesaleDetail['address']) ?></em><?php endif; ?>
                    </span>
                </div>
            </header>

            <div class="market-detail__hero">
                <figure class="market-detail__photo<?= $wholesaleUsesLogoImage ? ' is-logo-fallback' : '' ?>">
                    <img src="<?= frontend_velkoobchod_e($wholesaleImage) ?>" alt="<?= frontend_velkoobchod_e((string)$wholesaleDetail['name']) ?>" loading="eager">
                    <span><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_photo_label')) ?></span>
                </figure>
                <div
                    class="markets-map market-detail__map"
                    data-markets-map
                    data-points="<?= frontend_velkoobchod_e(json_encode($wholesaleMapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>"
                    data-empty="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty')) ?>"
                    data-label-all-cities="<?= frontend_velkoobchod_e(ui_text('markety.all_cities')) ?>"
                    data-label-branch="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_branch')) ?>"
                >
                    <div class="markets-map__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty')) ?></div>
                </div>
            </div>

            <div class="market-detail__info-grid">
                <section class="market-detail-card market-detail-opening" aria-labelledby="wholesale-opening-title">
                    <div class="market-detail-opening__head">
                        <h2 id="wholesale-opening-title"><?= frontend_velkoobchod_e(ui_text('markety.opening_title')) ?></h2>
                        <span class="market-detail-opening__status <?= !empty($wholesaleDetail['is_open']) ? 'is-open' : 'is-closed' ?>">
                            <?= frontend_velkoobchod_e(!empty($wholesaleDetail['is_open']) ? ui_text('markety.open_now') : ui_text('markety.closed_now')) ?>
                        </span>
                    </div>
                    <?php if (!empty($wholesaleDetail['opening_has_exception_today'])): ?>
                        <p class="market-detail-opening__exception">
                            <?= frontend_velkoobchod_e(ui_text('markety.opening_exception_today')) ?>
                        </p>
                    <?php endif; ?>
                    <dl>
                        <?php foreach ((array)$wholesaleDetail['opening_week'] as $row): ?>
                            <?php
                            $openingRowClasses = array_filter([
                                !empty($row['closed']) ? 'is-closed' : '',
                                !empty($row['is_today']) ? 'is-today' : '',
                                !empty($row['is_open']) ? 'is-open' : '',
                                !empty($row['is_exception']) ? 'is-exception' : '',
                            ]);
                            ?>
                            <div class="<?= frontend_velkoobchod_e(implode(' ', $openingRowClasses)) ?>">
                                <dt>
                                    <span><?= frontend_velkoobchod_e((string)$row['label']) ?></span>
                                    <?php if (!empty($row['is_today'])): ?><em><?= frontend_velkoobchod_e(ui_text('markety.today')) ?></em><?php endif; ?>
                                    <?php if (!empty($row['is_exception'])): ?><small><?= frontend_velkoobchod_e(ui_text('markety.exception')) ?></small><?php endif; ?>
                                    <?php if ((string)$row['note'] !== ''): ?><small><?= frontend_velkoobchod_e((string)$row['note']) ?></small><?php endif; ?>
                                </dt>
                                <dd>
                                    <?= frontend_velkoobchod_e((string)$row['time']) ?>
                                    <?php if (!empty($row['is_today'])): ?>
                                        <small><?= frontend_velkoobchod_e(!empty($row['is_open']) ? ui_text('markety.open_now') : ui_text('markety.closed_now')) ?></small>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <section class="market-detail-card market-detail-services" aria-labelledby="wholesale-services-title">
                    <h2 id="wholesale-services-title"><?= frontend_velkoobchod_e(ui_text('markety.services_title')) ?></h2>
                    <?php if ($wholesaleDetail['services'] !== []): ?>
                        <ul>
                            <?php foreach ((array)$wholesaleDetail['services'] as $service): ?>
                                <li><span aria-hidden="true">+</span><?= frontend_velkoobchod_e((string)$service) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.services_empty')) ?></p>
                    <?php endif; ?>
                </section>

                <section class="market-detail-card market-detail-contact" aria-labelledby="wholesale-contact-title">
                    <span class="market-detail-contact__icon" aria-hidden="true">↗</span>
                    <h2 id="wholesale-contact-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.branch_contact_title')) ?></h2>
                    <?php if ((string)$wholesaleDetail['address'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">⌖</span><span><?= frontend_velkoobchod_e((string)$wholesaleDetail['address']) ?></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['email'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">✉</span><span><a href="mailto:<?= frontend_velkoobchod_e((string)$wholesaleDetail['email']) ?>"><?= frontend_velkoobchod_e((string)$wholesaleDetail['email']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['phone'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">☎</span><span><a href="tel:<?= frontend_velkoobchod_e(preg_replace('~\s+~', '', (string)$wholesaleDetail['phone']) ?? '') ?>"><?= frontend_velkoobchod_e((string)$wholesaleDetail['phone']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['manager'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">•</span><span><?= frontend_velkoobchod_e(ui_text('markety.manager')) ?>: <?= frontend_velkoobchod_e((string)$wholesaleDetail['manager']) ?></span></p><?php endif; ?>
                </section>
            </div>

            <section class="market-detail-section wholesale-representatives" aria-labelledby="wholesale-detail-representatives-title">
                <div class="market-detail-section__head">
                    <div>
                        <h2 id="wholesale-detail-representatives-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_title')) ?></h2>
                        <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.representatives_detail_text')) ?></p>
                    </div>
                </div>
                <?php if ($wholesaleRepresentatives !== []): ?>
                    <div class="wholesale-representatives__grid">
                        <?php foreach ($wholesaleRepresentatives as $person): ?>
                            <?php $renderWholesaleRepresentativeCard($person); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_detail_empty')) ?></p>
                <?php endif; ?>
            </section>

            <section class="market-detail-section market-detail-jobs" aria-labelledby="wholesale-jobs-title">
                <h2 id="wholesale-jobs-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.jobs_title')) ?> <span>(<?= count($wholesaleJobs) ?>)</span></h2>
                <?php if ($wholesaleJobs !== []): ?>
                    <div class="market-detail-jobs__list">
                        <?php foreach ($wholesaleJobs as $job): ?>
                            <a href="<?= frontend_velkoobchod_e((string)$job['url']) ?>">
                                <span><strong><?= frontend_velkoobchod_e((string)$job['title']) ?></strong><small><?= frontend_velkoobchod_e((string)$wholesaleDetail['city']) ?><?= (string)$job['location'] !== '' ? ' - ' . frontend_velkoobchod_e((string)$job['location']) : '' ?></small></span>
                                <span aria-hidden="true">›</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.jobs_empty')) ?></p>
                <?php endif; ?>
            </section>

            <section class="market-detail-section market-detail-flyers" aria-labelledby="wholesale-flyers-title">
                <div class="market-detail-section__head">
                    <div>
                        <h2 id="wholesale-flyers-title"><?= frontend_velkoobchod_e(ui_text('markety.flyers_title')) ?></h2>
                    </div>
                    <a class="market-detail-section__link" href="/<?= frontend_velkoobchod_e($lang) ?>/akce?typ=<?= frontend_velkoobchod_e($wholesaleFlyerType) ?>">
                        <?= frontend_velkoobchod_e(ui_text('flyers.all')) ?>
                    </a>
                </div>
                <?php if ($wholesaleFlyers !== []): ?>
                    <div class="market-detail-flyers__grid">
                        <?php foreach ($wholesaleFlyers as $flyer): ?>
                            <?php
                            $flyerPages = array_values((array)($flyer['pages'] ?? []));
                            $flyerViewerId = 'wholesale-flyer-viewer-' . (int)$flyer['id'];
                            $flyerPagesJson = json_encode($flyerPages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            if (!is_string($flyerPagesJson)) {
                                $flyerPagesJson = '[]';
                            }
                            ?>
                            <article class="market-detail-flyer">
                                <a class="market-detail-flyer__image" href="<?= frontend_velkoobchod_e((string)$flyer['href']) ?>">
                                    <?php if ((string)$flyer['image'] !== ''): ?>
                                        <img src="<?= frontend_velkoobchod_e((string)$flyer['image']) ?>" alt="<?= frontend_velkoobchod_e((string)$flyer['title']) ?>" loading="lazy">
                                    <?php endif; ?>
                                </a>
                                <strong><?= frontend_velkoobchod_e((string)$flyer['title']) ?></strong>
                                <p><?= frontend_velkoobchod_e(function_exists('frontend_akce_validity_text') ? frontend_akce_validity_text($flyer) : '') ?></p>
                                <div>
                                    <?php if ((string)$flyer['pdf'] !== ''): ?><a href="<?= frontend_velkoobchod_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener"><?= frontend_velkoobchod_e(ui_text('flyers.pdf')) ?></a><?php endif; ?>
                                    <?php if ($flyerPages !== []): ?>
                                        <a href="<?= frontend_velkoobchod_e((string)$flyer['href']) ?>" data-akce-viewer-open="<?= frontend_velkoobchod_e($flyerViewerId) ?>"><?= frontend_velkoobchod_e(ui_text('flyers.browse')) ?></a>
                                    <?php else: ?>
                                        <a href="<?= frontend_velkoobchod_e((string)$flyer['href']) ?>"><?= frontend_velkoobchod_e(ui_text('flyers.browse')) ?></a>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php if ($flyerPages !== []): ?>
                                <div class="akce-flip-viewer akce-flip-viewer--modal"
                                     id="<?= frontend_velkoobchod_e($flyerViewerId) ?>"
                                     data-akce-public-viewer
                                     data-akce-viewer-modal
                                     data-pages="<?= frontend_velkoobchod_e($flyerPagesJson) ?>"
                                     aria-modal="true"
                                     role="dialog"
                                     aria-label="<?= frontend_velkoobchod_e((string)$flyer['title']) ?>">
                                    <button type="button"
                                            class="akce-flip-viewer__close"
                                            data-akce-viewer-action="close"
                                            aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.close_viewer')) ?>">
                                        ×
                                    </button>
                                    <div class="akce-flip-viewer__toolbar">
                                        <?php if ((string)$flyer['pdf'] !== ''): ?>
                                            <a class="akce-flip-viewer__toolbar-link akce-flip-viewer__toolbar-link--pdf" href="<?= frontend_velkoobchod_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.download_pdf')) ?>"><?= frontend_velkoobchod_e(ui_text('flyers.download_pdf')) ?></a>
                                        <?php endif; ?>
                                        <button type="button" data-akce-viewer-action="first" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.first_page')) ?>">‹‹</button>
                                        <button type="button" data-akce-viewer-action="prev" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.prev_page')) ?>">‹</button>
                                        <input class="akce-flip-viewer__page-input" type="text" inputmode="numeric" pattern="[0-9]*" value="1 / <?= count($flyerPages) ?>" data-akce-viewer-page data-page-word="<?= frontend_velkoobchod_e(ui_text('akce.page')) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.go_to_page')) ?>">
                                        <button type="button" data-akce-viewer-action="next" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.next_page')) ?>">›</button>
                                        <button type="button" data-akce-viewer-action="last" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.last_page')) ?>">››</button>
                                        <button type="button" data-akce-viewer-action="autoplay" data-label-start="<?= frontend_velkoobchod_e(ui_text('flyers.autoplay')) ?>" data-label-stop="<?= frontend_velkoobchod_e(ui_text('flyers.autoplay_stop')) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.autoplay')) ?>" aria-pressed="false">▶</button>
                                        <button type="button" data-akce-viewer-action="toggle-thumbs" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.toggle_thumbs')) ?>" title="<?= frontend_velkoobchod_e(ui_text('flyers.toggle_thumbs')) ?>" aria-pressed="true">▦</button>
                                        <button type="button" data-akce-viewer-action="zoom-out" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_out')) ?>">−</button>
                                        <button type="button" data-akce-viewer-action="zoom-reset" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_reset')) ?>">100%</button>
                                        <button type="button" data-akce-viewer-action="zoom-in" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_in')) ?>">+</button>
                                        <button type="button" class="akce-flip-viewer__magnifier" data-akce-viewer-action="zoom-toggle" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.magnifier')) ?>" title="<?= frontend_velkoobchod_e(ui_text('flyers.magnifier')) ?>" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m15.5 15.5 5 5M10.5 7v7M7 10.5h7"></path></svg></button>
                                        <button type="button" data-akce-viewer-action="fullscreen" data-label-enter="<?= frontend_velkoobchod_e(ui_text('flyers.fullscreen')) ?>" data-label-exit="<?= frontend_velkoobchod_e(ui_text('flyers.exit_fullscreen')) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.fullscreen')) ?>" aria-pressed="false">⛶</button>
                                    </div>
                                    <div class="akce-flip-viewer__body">
                                        <div class="akce-flip-viewer__book-wrap">
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--prev" data-akce-viewer-action="prev" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.prev_page')) ?>">‹</button>
                                            <div class="akce-flip-viewer__book-stage" data-akce-viewer-stage>
                                                <div class="akce-flip-viewer__book" data-akce-viewer-book></div>
                                            </div>
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--next" data-akce-viewer-action="next" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.next_page')) ?>">›</button>
                                        </div>
                                        <div class="akce-flip-viewer__thumbs" data-akce-viewer-thumbs aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.page_thumbs')) ?>"></div>
                                    </div>
                                    <div class="akce-viewer-simple" data-akce-viewer-fallback>
                                        <?php foreach ($flyerPages as $page): ?>
                                            <figure class="akce-viewer-simple__page">
                                                <img data-src="<?= frontend_velkoobchod_e((string)($page['src'] ?? '')) ?>" alt="<?= frontend_velkoobchod_e((string)($page['label'] ?? '')) ?>" loading="lazy">
                                                <figcaption><?= frontend_velkoobchod_e((string)($page['label'] ?? '')) ?></figcaption>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('akce.no_current')) ?></p>
                <?php endif; ?>
            </section>

            <?php if ($wholesalePhotos !== []): ?>
                <section
                    class="market-detail-section market-detail-gallery"
                    aria-labelledby="wholesale-gallery-title"
                    data-market-gallery
                >
                    <div class="market-detail-section__head">
                        <div>
                            <h2 id="wholesale-gallery-title"><?= frontend_velkoobchod_e(ui_text('markety.gallery_title')) ?></h2>
                            <p><?= frontend_velkoobchod_e(sprintf(stat_vyraz_text('velkoobchod.gallery_intro'), count($wholesalePhotos))) ?></p>
                        </div>
                    </div>
                    <div class="market-detail-gallery__grid">
                        <?php foreach ($wholesalePhotos as $index => $photo): ?>
                            <button
                                type="button"
                                class="market-detail-gallery__item"
                                data-market-gallery-item
                                data-market-gallery-index="<?= (int)$index ?>"
                                data-full="<?= frontend_velkoobchod_e((string)$photo['image']) ?>"
                                data-title="<?= frontend_velkoobchod_e((string)($photo['title'] !== '' ? $photo['title'] : $wholesaleDetail['name'])) ?>"
                                aria-label="<?= frontend_velkoobchod_e(sprintf(ui_text('markety.gallery_open_photo'), $index + 1)) ?>"
                            >
                                <img src="<?= frontend_velkoobchod_e((string)$photo['thumb']) ?>" alt="<?= frontend_velkoobchod_e((string)($photo['title'] !== '' ? $photo['title'] : $wholesaleDetail['name'])) ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div
                        class="market-gallery-lightbox"
                        data-market-gallery-lightbox
                        data-label-close="<?= frontend_velkoobchod_e(ui_text('common.close')) ?>"
                        data-label-prev="<?= frontend_velkoobchod_e(ui_text('common.previous')) ?>"
                        data-label-next="<?= frontend_velkoobchod_e(ui_text('common.next')) ?>"
                        hidden
                    ></div>
                </section>
            <?php endif; ?>

            <section class="markets-contact">
                <div class="markets-contact__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3a8 8 0 0 0-8 8v3.2A2.8 2.8 0 0 0 6.8 17H8v-6H5.8A6.2 6.2 0 0 1 18.2 11H16v6h1.6a4.6 4.6 0 0 1-4.3 3H11v-2h2.3a2.6 2.6 0 0 0 2.4-1.6A2.8 2.8 0 0 0 20 14.2V11a8 8 0 0 0-8-8Z"/></svg>
                </div>
                <div>
                    <h2><?= frontend_velkoobchod_e(ui_text('markety.contact_title')) ?></h2>
                    <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.contact_text')) ?></p>
                </div>
                <a href="/<?= frontend_velkoobchod_e($lang) ?>/kontakty">
                    <?= frontend_velkoobchod_e(ui_text('markety.contact_button')) ?>
                    <span aria-hidden="true">›</span>
                </a>
            </section>
        </div>
    </section>
    <?php
    return;
endif;

$wholesaleBranches = function_exists('frontend_velkoobchod_branches') ? frontend_velkoobchod_branches($lang) : [];
$wholesaleBranchPoints = function_exists('frontend_velkoobchod_branch_points') ? frontend_velkoobchod_branch_points($wholesaleBranches) : [];
$wholesaleAreas = function_exists('frontend_velkoobchod_map_areas') ? frontend_velkoobchod_map_areas() : ['type' => 'FeatureCollection', 'features' => []];
$wholesalePlaces = function_exists('frontend_velkoobchod_availability_places') ? frontend_velkoobchod_availability_places() : [];
$wholesaleRepresentatives = function_exists('frontend_velkoobchod_representatives') ? frontend_velkoobchod_representatives($lang) : [];
$wholesaleIntro = function_exists('stat_text') ? trim((string)(stat_text('velkoobchod_intro', $lang) ?? '')) : '';
$wholesaleText = function_exists('stat_text') ? trim((string)(stat_text('velkoobchod_text', $lang) ?? '')) : '';
$wholesaleRouterText = (function_exists('stat_vyraz') && function_exists('plain_text')) ? plain_text(stat_vyraz('home.router.velkoobchod.text', $lang)) : '';
?>
<section class="markets-page wholesale-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb')) ?>">
            <ol>
                <li>
                    <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb_home')) ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li><span aria-current="page"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title')) ?></span></li>
            </ol>
        </nav>

        <header class="markets-page__head">
            <div class="home-router__card home-router__card--light markets-page-router">
                <span class="home-router__brand">
                    <img src="/img/design/logo_qanto_router.png" alt="Qanto" loading="lazy">
                </span>
                <span class="home-router__text">
                    <small>02</small>
                    <h1><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('router.velkoobchod.title')) ?></a></h1>
                    <?php if ($wholesaleRouterText !== ''): ?><em><?= frontend_velkoobchod_e($wholesaleRouterText) ?></em><?php endif; ?>
                </span>
            </div>
            <div class="markets-page__intro">
                <?php if ($wholesaleIntro !== ''): ?>
                    <?= $wholesaleIntro ?>
                <?php else: ?>
                    <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.intro_fallback')) ?></p>
                <?php endif; ?>
            </div>
        </header>

        <section class="markets-finder wholesale-finder" aria-label="<?= frontend_velkoobchod_e(ui_text('velkoobchod.finder_title')) ?>">
            <div class="markets-finder__sidebar">
                <div class="markets-finder__header">
                    <div>
                        <h2><?= frontend_velkoobchod_e(ui_text('velkoobchod.finder_title')) ?></h2>
                        <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.finder_text')) ?></p>
                    </div>

                    <section
                        class="wholesale-availability wholesale-availability--compact"
                        data-wholesale-availability
                        data-label-served="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_served')) ?>"
                        data-label-excluded="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_excluded')) ?>"
                        data-label-review="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_review')) ?>"
                        data-label-not-served="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_not_served')) ?>"
                        data-label-no-result="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_no_result')) ?>"
                        data-label-contact="<?= frontend_velkoobchod_e(ui_text('velkoobchod.contact_person')) ?>"
                        data-label-no-contact="<?= frontend_velkoobchod_e(ui_text('velkoobchod.no_contact')) ?>"
                    >
                        <form class="wholesale-availability__form" data-wholesale-availability-form>
                            <label class="visually-hidden" for="wholesale_availability_query"><?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_label')) ?></label>
                            <div class="wholesale-availability__search">
                                <input
                                    type="search"
                                    id="wholesale_availability_query"
                                    autocomplete="off"
                                    placeholder="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_placeholder')) ?>"
                                    data-wholesale-availability-input
                                >
                                <button type="submit" aria-label="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_submit')) ?>">
                                    <span aria-hidden="true"></span>
                                </button>
                                <div class="wholesale-availability__suggestions" data-wholesale-availability-suggestions hidden></div>
                            </div>
                            <div class="wholesale-availability__result" data-wholesale-availability-result hidden></div>
                        </form>
                        <script type="application/json" data-wholesale-availability-places><?= frontend_velkoobchod_json($wholesalePlaces) ?></script>
                    </section>

                    <button
                        type="button"
                        class="markets-mobile-toggle wholesale-mobile-toggle"
                        data-wholesale-mobile-toggle
                        data-label-map="<?= frontend_velkoobchod_e(ui_text('markety.show_map')) ?>"
                        data-label-list="<?= frontend_velkoobchod_e(ui_text('markety.show_list')) ?>"
                        aria-pressed="false"
                    >
                        <span data-wholesale-mobile-toggle-label><?= frontend_velkoobchod_e(ui_text('markety.show_map')) ?></span>
                    </button>
                </div>

                <div class="markets-list wholesale-branches-list">
                    <?php if ($wholesaleBranches === []): ?>
                        <div class="markets-list__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.branches_empty')) ?></div>
                    <?php else: ?>
                        <?php foreach ($wholesaleBranches as $branch): ?>
                            <article
                                class="markets-card"
                                data-wholesale-branch-card
                                data-branch-id="<?= (int)$branch['id'] ?>"
                            >
                                <button type="button" class="markets-card__button" data-wholesale-branch-focus="<?= (int)$branch['id'] ?>">
                                    <span class="markets-card__image is-logo-fallback">
                                        <img src="/img/design/logo_qanto_router.png" alt="<?= frontend_velkoobchod_e((string)$branch['name']) ?>" loading="lazy">
                                    </span>
                                    <span class="markets-card__body">
                                        <strong><?= frontend_velkoobchod_e((string)$branch['name']) ?></strong>
                                        <?php if ((string)$branch['address'] !== ''): ?><span><?= frontend_velkoobchod_e((string)$branch['address']) ?></span><?php endif; ?>
                                        <small class="<?= $branch['is_open'] ? 'is-open' : '' ?>"><?= frontend_velkoobchod_e((string)$branch['opening_label']) ?></small>
                                    </span>
                                </button>
                                <a class="markets-card__detail" href="<?= frontend_velkoobchod_e(frontend_markety_detail_url($branch, $lang, 'velkoobchod')) ?>"><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_link')) ?></a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div
                class="wholesale-map"
                data-wholesale-map
                data-empty="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty')) ?>"
                data-label-branch="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_branch')) ?>"
                data-label-reset="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_reset')) ?>"
            >
                <div class="markets-map__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty')) ?></div>
                <script type="application/json" data-wholesale-map-areas><?= frontend_velkoobchod_json($wholesaleAreas) ?></script>
                <script type="application/json" data-wholesale-map-branches><?= frontend_velkoobchod_json($wholesaleBranchPoints) ?></script>
            </div>
        </section>

        <section class="wholesale-representatives" aria-labelledby="wholesale-representatives-title">
            <div class="market-detail-section__head">
                <div>
                    <h2 id="wholesale-representatives-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_title')) ?></h2>
                    <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.representatives_text')) ?></p>
                </div>
            </div>
            <div class="wholesale-branch-filter" data-wholesale-branch-filter>
                <button type="button" class="is-active" data-wholesale-branch-filter-button=""><?= frontend_velkoobchod_e(ui_text('velkoobchod.all_warehouses')) ?></button>
                <?php foreach ($wholesaleBranches as $branch): ?>
                    <button type="button" data-wholesale-branch-filter-button="<?= (int)$branch['id'] ?>"><?= frontend_velkoobchod_e((string)$branch['name']) ?></button>
                <?php endforeach; ?>
            </div>

            <?php if ($wholesaleRepresentatives !== []): ?>
                <div class="wholesale-representatives__grid">
                    <?php foreach ($wholesaleRepresentatives as $person): ?>
                        <?php $renderWholesaleRepresentativeCard($person, true); ?>
                    <?php endforeach; ?>
                </div>
                <p class="market-detail__empty" data-wholesale-representatives-empty hidden><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_empty_filter')) ?></p>
            <?php else: ?>
                <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_empty')) ?></p>
            <?php endif; ?>
        </section>

        <?php if ($wholesaleText !== ''): ?>
            <section class="markets-text">
                <?= $wholesaleText ?>
            </section>
        <?php endif; ?>

        <section class="markets-contact">
            <div class="markets-contact__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3a8 8 0 0 0-8 8v3.2A2.8 2.8 0 0 0 6.8 17H8v-6H5.8A6.2 6.2 0 0 1 18.2 11H16v6h1.6a4.6 4.6 0 0 1-4.3 3H11v-2h2.3a2.6 2.6 0 0 0 2.4-1.6A2.8 2.8 0 0 0 20 14.2V11a8 8 0 0 0-8-8Z"/></svg>
            </div>
            <div>
                <h2><?= frontend_velkoobchod_e(ui_text('markety.contact_title')) ?></h2>
                <p><?= frontend_velkoobchod_e(stat_vyraz_text('velkoobchod.contact_text')) ?></p>
            </div>
            <a href="/<?= frontend_velkoobchod_e($lang) ?>/kontakty">
                <?= frontend_velkoobchod_e(ui_text('markety.contact_button')) ?>
                <span aria-hidden="true">›</span>
            </a>
        </section>
    </div>
</section>
