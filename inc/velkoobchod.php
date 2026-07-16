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
                <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
                    <ol>
                        <li>
                            <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.home', 'Domů')) ?>">
                                <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                            </a>
                        </li>
                        <li><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title', 'Velkoobchod')) ?></a></li>
                        <li><span aria-current="page"><?= frontend_velkoobchod_e(ui_text('common.page_not_found', 'Stránka nebyla nalezena.')) ?></span></li>
                    </ol>
                </nav>
                <div class="alert alert-warning"><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_not_found', 'Velkoobchod nebyl nalezen.')) ?></div>
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
            <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
                <ol>
                    <li>
                        <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.home', 'Domů')) ?>">
                            <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                        </a>
                    </li>
                    <li><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title', 'Velkoobchod')) ?></a></li>
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
                    <span><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_photo_label', 'Velkoobchodní sklad')) ?></span>
                </figure>
                <div
                    class="markets-map market-detail__map"
                    data-markets-map
                    data-points="<?= frontend_velkoobchod_e(json_encode($wholesaleMapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>"
                    data-empty="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty', 'Pro mapu nejsou dostupná data závozových obcí.')) ?>"
                    data-label-all-cities="<?= frontend_velkoobchod_e(ui_text('markety.all_cities', 'Celá ČR')) ?>"
                    data-label-branch="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_branch', 'Velkoobchod')) ?>"
                >
                    <div class="markets-map__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty', 'Pro mapu nejsou dostupná data závozových obcí.')) ?></div>
                </div>
            </div>

            <div class="market-detail__info-grid">
                <section class="market-detail-card market-detail-opening" aria-labelledby="wholesale-opening-title">
                    <div class="market-detail-opening__head">
                        <h2 id="wholesale-opening-title"><?= frontend_velkoobchod_e(ui_text('markety.opening_title', 'Otevírací doba')) ?></h2>
                        <span class="market-detail-opening__status <?= !empty($wholesaleDetail['is_open']) ? 'is-open' : 'is-closed' ?>">
                            <?= frontend_velkoobchod_e(!empty($wholesaleDetail['is_open']) ? ui_text('markety.open_now', 'Otevřeno') : ui_text('markety.closed_now', 'Zavřeno')) ?>
                        </span>
                    </div>
                    <?php if (!empty($wholesaleDetail['opening_has_exception_today'])): ?>
                        <p class="market-detail-opening__exception">
                            <?= frontend_velkoobchod_e(ui_text('markety.opening_exception_today', 'Dnes platí upravená otevírací doba.')) ?>
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
                                    <?php if (!empty($row['is_today'])): ?><em><?= frontend_velkoobchod_e(ui_text('markety.today', 'Dnes')) ?></em><?php endif; ?>
                                    <?php if (!empty($row['is_exception'])): ?><small><?= frontend_velkoobchod_e(ui_text('markety.exception', 'Výjimka')) ?></small><?php endif; ?>
                                    <?php if ((string)$row['note'] !== ''): ?><small><?= frontend_velkoobchod_e((string)$row['note']) ?></small><?php endif; ?>
                                </dt>
                                <dd>
                                    <?= frontend_velkoobchod_e((string)$row['time']) ?>
                                    <?php if (!empty($row['is_today'])): ?>
                                        <small><?= frontend_velkoobchod_e(!empty($row['is_open']) ? ui_text('markety.open_now', 'Otevřeno') : ui_text('markety.closed_now', 'Zavřeno')) ?></small>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <section class="market-detail-card market-detail-services" aria-labelledby="wholesale-services-title">
                    <h2 id="wholesale-services-title"><?= frontend_velkoobchod_e(ui_text('markety.services_title', 'Služby')) ?></h2>
                    <?php if ($wholesaleDetail['services'] !== []): ?>
                        <ul>
                            <?php foreach ((array)$wholesaleDetail['services'] as $service): ?>
                                <li><span aria-hidden="true">+</span><?= frontend_velkoobchod_e((string)$service) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.services_empty', 'Služby pro tento velkoobchod doplníme.')) ?></p>
                    <?php endif; ?>
                </section>

                <section class="market-detail-card market-detail-contact" aria-labelledby="wholesale-contact-title">
                    <span class="market-detail-contact__icon" aria-hidden="true">↗</span>
                    <h2 id="wholesale-contact-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.branch_contact_title', 'Kontakt na velkoobchod')) ?></h2>
                    <?php if ((string)$wholesaleDetail['address'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">⌖</span><span><?= frontend_velkoobchod_e((string)$wholesaleDetail['address']) ?></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['email'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">✉</span><span><a href="mailto:<?= frontend_velkoobchod_e((string)$wholesaleDetail['email']) ?>"><?= frontend_velkoobchod_e((string)$wholesaleDetail['email']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['phone'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">☎</span><span><a href="tel:<?= frontend_velkoobchod_e(preg_replace('~\s+~', '', (string)$wholesaleDetail['phone']) ?? '') ?>"><?= frontend_velkoobchod_e((string)$wholesaleDetail['phone']) ?></a></span></p><?php endif; ?>
                    <?php if ((string)$wholesaleDetail['manager'] !== ''): ?><p><span class="market-detail-contact__bullet" aria-hidden="true">•</span><span><?= frontend_velkoobchod_e(ui_text('markety.manager', 'Vedoucí')) ?>: <?= frontend_velkoobchod_e((string)$wholesaleDetail['manager']) ?></span></p><?php endif; ?>
                </section>
            </div>

            <section class="market-detail-section wholesale-representatives" aria-labelledby="wholesale-detail-representatives-title">
                <div class="market-detail-section__head">
                    <div>
                        <h2 id="wholesale-detail-representatives-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_title', 'Obchodní zástupci')) ?></h2>
                        <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_detail_text', 'Obchodní zástupci přiřazení k tomuto velkoobchodnímu skladu.')) ?></p>
                    </div>
                </div>
                <?php if ($wholesaleRepresentatives !== []): ?>
                    <div class="wholesale-representatives__grid">
                        <?php foreach ($wholesaleRepresentatives as $person): ?>
                            <?php $renderWholesaleRepresentativeCard($person); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_detail_empty', 'K tomuto velkoobchodu nejsou přiřazeni žádní obchodní zástupci.')) ?></p>
                <?php endif; ?>
            </section>

            <section class="market-detail-section market-detail-jobs" aria-labelledby="wholesale-jobs-title">
                <h2 id="wholesale-jobs-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.jobs_title', 'Volná místa ve velkoobchodu')) ?> <span>(<?= count($wholesaleJobs) ?>)</span></h2>
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
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.jobs_empty', 'V tomto velkoobchodu teď nejsou vypsaná volná místa.')) ?></p>
                <?php endif; ?>
            </section>

            <section class="market-detail-section market-detail-flyers" aria-labelledby="wholesale-flyers-title">
                <div class="market-detail-section__head">
                    <div>
                        <h2 id="wholesale-flyers-title"><?= frontend_velkoobchod_e(ui_text('markety.flyers_title', 'Letáky')) ?></h2>
                    </div>
                    <a class="market-detail-section__link" href="/<?= frontend_velkoobchod_e($lang) ?>/akce?typ=<?= frontend_velkoobchod_e($wholesaleFlyerType) ?>">
                        <?= frontend_velkoobchod_e(ui_text('flyers.all', 'Všechny letáky')) ?>
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
                                    <?php if ((string)$flyer['pdf'] !== ''): ?><a href="<?= frontend_velkoobchod_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener"><?= frontend_velkoobchod_e(ui_text('flyers.pdf', 'PDF')) ?></a><?php endif; ?>
                                    <?php if ($flyerPages !== []): ?>
                                        <a href="<?= frontend_velkoobchod_e((string)$flyer['href']) ?>" data-akce-viewer-open="<?= frontend_velkoobchod_e($flyerViewerId) ?>"><?= frontend_velkoobchod_e(ui_text('flyers.browse', 'Prolistovat')) ?></a>
                                    <?php else: ?>
                                        <a href="<?= frontend_velkoobchod_e((string)$flyer['href']) ?>"><?= frontend_velkoobchod_e(ui_text('flyers.browse', 'Prolistovat')) ?></a>
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
                                            aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.close_viewer', 'Zavřít prohlížeč')) ?>">
                                        ×
                                    </button>
                                    <div class="akce-flip-viewer__toolbar">
                                        <?php if ((string)$flyer['pdf'] !== ''): ?>
                                            <a class="akce-flip-viewer__toolbar-link" href="<?= frontend_velkoobchod_e((string)$flyer['pdf']) ?>" target="_blank" rel="noopener"><?= frontend_velkoobchod_e(ui_text('flyers.download_pdf', 'Stáhnout PDF')) ?></a>
                                        <?php endif; ?>
                                        <button type="button" data-akce-viewer-action="first" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.first_page', 'První strana')) ?>">‹‹</button>
                                        <button type="button" data-akce-viewer-action="prev" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.prev_page', 'Předchozí strana')) ?>">‹</button>
                                        <span data-akce-viewer-page data-page-word="<?= frontend_velkoobchod_e(ui_text('akce.page', 'Strana')) ?>"><?= frontend_velkoobchod_e(ui_text('akce.page', 'Strana')) ?> 1 / <?= count($flyerPages) ?></span>
                                        <button type="button" data-akce-viewer-action="next" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.next_page', 'Další strana')) ?>">›</button>
                                        <button type="button" data-akce-viewer-action="last" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.last_page', 'Poslední strana')) ?>">››</button>
                                        <button type="button" data-akce-viewer-action="zoom-out" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_out', 'Zmenšit')) ?>">−</button>
                                        <button type="button" data-akce-viewer-action="zoom-reset" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_reset', 'Původní velikost')) ?>">100%</button>
                                        <button type="button" data-akce-viewer-action="zoom-in" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.zoom_in', 'Zvětšit')) ?>">+</button>
                                        <button type="button" data-akce-viewer-action="fullscreen" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.fullscreen', 'Celá obrazovka')) ?>">⛶</button>
                                    </div>
                                    <div class="akce-flip-viewer__body">
                                        <div class="akce-flip-viewer__book-wrap">
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--prev" data-akce-viewer-action="prev" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.prev_page', 'Předchozí strana')) ?>">‹</button>
                                            <div class="akce-flip-viewer__book-stage" data-akce-viewer-stage>
                                                <div class="akce-flip-viewer__book" data-akce-viewer-book></div>
                                            </div>
                                            <button type="button" class="akce-flip-viewer__side akce-flip-viewer__side--next" data-akce-viewer-action="next" aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.next_page', 'Další strana')) ?>">›</button>
                                        </div>
                                        <div class="akce-flip-viewer__thumbs" data-akce-viewer-thumbs aria-label="<?= frontend_velkoobchod_e(ui_text('flyers.page_thumbs', 'Náhledy stran')) ?>"></div>
                                    </div>
                                    <div class="akce-viewer-simple" data-akce-viewer-fallback>
                                        <?php foreach ($flyerPages as $page): ?>
                                            <figure class="akce-viewer-simple__page">
                                                <img src="<?= frontend_velkoobchod_e((string)($page['src'] ?? '')) ?>" alt="<?= frontend_velkoobchod_e((string)($page['label'] ?? '')) ?>" loading="lazy">
                                                <figcaption><?= frontend_velkoobchod_e((string)($page['label'] ?? '')) ?></figcaption>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('akce.no_current', 'Aktuálně zde nejsou žádné platné letáky.')) ?></p>
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
                            <h2 id="wholesale-gallery-title"><?= frontend_velkoobchod_e(ui_text('markety.gallery_title', 'Fotogalerie')) ?></h2>
                            <p><?= frontend_velkoobchod_e(sprintf(ui_text('velkoobchod.gallery_intro', 'Prohlédněte si %d fotografií tohoto velkoobchodu.'), count($wholesalePhotos))) ?></p>
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
                                aria-label="<?= frontend_velkoobchod_e(sprintf(ui_text('markety.gallery_open_photo', 'Otevřít fotografii %d'), $index + 1)) ?>"
                            >
                                <img src="<?= frontend_velkoobchod_e((string)$photo['thumb']) ?>" alt="<?= frontend_velkoobchod_e((string)($photo['title'] !== '' ? $photo['title'] : $wholesaleDetail['name'])) ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div
                        class="market-gallery-lightbox"
                        data-market-gallery-lightbox
                        data-label-close="<?= frontend_velkoobchod_e(ui_text('common.close', 'Zavřít')) ?>"
                        data-label-prev="<?= frontend_velkoobchod_e(ui_text('common.previous', 'Předchozí')) ?>"
                        data-label-next="<?= frontend_velkoobchod_e(ui_text('common.next', 'Další')) ?>"
                        hidden
                    ></div>
                </section>
            <?php endif; ?>

            <section class="markets-contact">
                <div class="markets-contact__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3a8 8 0 0 0-8 8v3.2A2.8 2.8 0 0 0 6.8 17H8v-6H5.8A6.2 6.2 0 0 1 18.2 11H16v6h1.6a4.6 4.6 0 0 1-4.3 3H11v-2h2.3a2.6 2.6 0 0 0 2.4-1.6A2.8 2.8 0 0 0 20 14.2V11a8 8 0 0 0-8-8Z"/></svg>
                </div>
                <div>
                    <h2><?= frontend_velkoobchod_e(ui_text('markety.contact_title', 'Kontaktujte nás')) ?></h2>
                    <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.contact_text', 'Chcete začít odebírat zboží velkoobchodně? Ozvěte se nám a najdeme vhodné řešení.')) ?></p>
                </div>
                <a href="/<?= frontend_velkoobchod_e($lang) ?>/kontakty">
                    <?= frontend_velkoobchod_e(ui_text('markety.contact_button', 'Zobrazit kontakty')) ?>
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
        <nav class="site-breadcrumb" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.breadcrumb', 'Drobečková navigace')) ?>">
            <ol>
                <li>
                    <a href="/<?= frontend_velkoobchod_e($lang) ?>" aria-label="<?= frontend_velkoobchod_e(ui_text('aria.home', 'Domů')) ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li><span aria-current="page"><?= frontend_velkoobchod_e(ui_text('velkoobchod.title', 'Velkoobchod')) ?></span></li>
            </ol>
        </nav>

        <header class="markets-page__head">
            <div class="home-router__card home-router__card--light markets-page-router">
                <span class="home-router__brand">
                    <img src="/img/design/logo_qanto_router.png" alt="Qanto" loading="lazy">
                </span>
                <span class="home-router__text">
                    <small>02</small>
                    <h1><a href="/<?= frontend_velkoobchod_e($lang) ?>/velkoobchod"><?= frontend_velkoobchod_e(ui_text('router.velkoobchod.title', 'velkoobchod')) ?></a></h1>
                    <?php if ($wholesaleRouterText !== ''): ?><em><?= frontend_velkoobchod_e($wholesaleRouterText) ?></em><?php endif; ?>
                </span>
            </div>
            <div class="markets-page__intro">
                <?php if ($wholesaleIntro !== ''): ?>
                    <?= $wholesaleIntro ?>
                <?php else: ?>
                    <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.intro_fallback', 'Zásobujeme firmy, gastro provozy, prodejny a další zákazníky v regionech, které dlouhodobě obsluhujeme z našich velkoobchodních skladů.')) ?></p>
                <?php endif; ?>
            </div>
        </header>

        <section class="markets-finder wholesale-finder" aria-label="<?= frontend_velkoobchod_e(ui_text('velkoobchod.finder_title', 'Velkoobchodní pobočky')) ?>">
            <div class="markets-finder__sidebar">
                <div class="markets-finder__header">
                    <div>
                        <h2><?= frontend_velkoobchod_e(ui_text('velkoobchod.finder_title', 'Velkoobchodní pobočky')) ?></h2>
                        <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.finder_text', 'Seznam skladů a mapa oblastí, do kterých pravidelně zavážíme.')) ?></p>
                    </div>

                    <section
                        class="wholesale-availability wholesale-availability--compact"
                        data-wholesale-availability
                        data-label-served="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_served', 'Do této obce zavážíme.')) ?>"
                        data-label-excluded="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_excluded', 'Do této obce standardně nezavážíme.')) ?>"
                        data-label-review="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_review', 'Dostupnost závozu v této obci ověřujeme.')) ?>"
                        data-label-not-served="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_not_served', 'Do této obce aktuálně nezavážíme.')) ?>"
                        data-label-no-result="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_no_result', 'Obec jsme v číselníku nenašli. Zkuste prosím zadat přesnější název nebo PSČ.')) ?>"
                        data-label-contact="<?= frontend_velkoobchod_e(ui_text('velkoobchod.contact_person', 'Kontaktní osoba')) ?>"
                        data-label-no-contact="<?= frontend_velkoobchod_e(ui_text('velkoobchod.no_contact', 'Kontakt doplníme.')) ?>"
                    >
                        <form class="wholesale-availability__form" data-wholesale-availability-form>
                            <label class="visually-hidden" for="wholesale_availability_query"><?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_label', 'Obec nebo PSČ')) ?></label>
                            <div class="wholesale-availability__search">
                                <input
                                    type="search"
                                    id="wholesale_availability_query"
                                    autocomplete="off"
                                    placeholder="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_placeholder', 'Např. Svitavy nebo 568 02')) ?>"
                                    data-wholesale-availability-input
                                >
                                <button type="submit" aria-label="<?= frontend_velkoobchod_e(ui_text('velkoobchod.availability_submit', 'Ověřit')) ?>">
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
                        data-label-map="<?= frontend_velkoobchod_e(ui_text('markety.show_map', 'Zobrazit mapu')) ?>"
                        data-label-list="<?= frontend_velkoobchod_e(ui_text('markety.show_list', 'Zobrazit seznam')) ?>"
                        aria-pressed="false"
                    >
                        <span data-wholesale-mobile-toggle-label><?= frontend_velkoobchod_e(ui_text('markety.show_map', 'Zobrazit mapu')) ?></span>
                    </button>
                </div>

                <div class="markets-list wholesale-branches-list">
                    <?php if ($wholesaleBranches === []): ?>
                        <div class="markets-list__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.branches_empty', 'Aktuálně nejsou dostupné žádné velkoobchodní sklady.')) ?></div>
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
                                <a class="markets-card__detail" href="<?= frontend_velkoobchod_e(frontend_markety_detail_url($branch, $lang, 'velkoobchod')) ?>"><?= frontend_velkoobchod_e(ui_text('velkoobchod.detail_link', 'Detail velkoobchodu')) ?></a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div
                class="wholesale-map"
                data-wholesale-map
                data-empty="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty', 'Pro mapu nejsou dostupná data závozových obcí.')) ?>"
                data-label-branch="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_branch', 'Velkoobchod')) ?>"
                data-label-reset="<?= frontend_velkoobchod_e(ui_text('velkoobchod.map_reset', 'Celá ČR')) ?>"
            >
                <div class="markets-map__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.map_empty', 'Pro mapu nejsou dostupná data závozových obcí.')) ?></div>
                <script type="application/json" data-wholesale-map-areas><?= frontend_velkoobchod_json($wholesaleAreas) ?></script>
                <script type="application/json" data-wholesale-map-branches><?= frontend_velkoobchod_json($wholesaleBranchPoints) ?></script>
            </div>
        </section>

        <section class="wholesale-representatives" aria-labelledby="wholesale-representatives-title">
            <div class="market-detail-section__head">
                <div>
                    <h2 id="wholesale-representatives-title"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_title', 'Obchodní zástupci')) ?></h2>
                    <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_text', 'Vyberte sklad a zobrazte příslušné obchodní zástupce.')) ?></p>
                </div>
            </div>
            <div class="wholesale-branch-filter" data-wholesale-branch-filter>
                <button type="button" class="is-active" data-wholesale-branch-filter-button=""><?= frontend_velkoobchod_e(ui_text('velkoobchod.all_warehouses', 'Všechny sklady')) ?></button>
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
                <p class="market-detail__empty" data-wholesale-representatives-empty hidden><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_empty_filter', 'Pro vybraný sklad nejsou dostupní žádní obchodní zástupci.')) ?></p>
            <?php else: ?>
                <p class="market-detail__empty"><?= frontend_velkoobchod_e(ui_text('velkoobchod.representatives_empty', 'Obchodní zástupce doplníme.')) ?></p>
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
                <h2><?= frontend_velkoobchod_e(ui_text('markety.contact_title', 'Kontaktujte nás')) ?></h2>
                <p><?= frontend_velkoobchod_e(ui_text('velkoobchod.contact_text', 'Chcete začít odebírat zboží velkoobchodně? Ozvěte se nám a najdeme vhodné řešení.')) ?></p>
            </div>
            <a href="/<?= frontend_velkoobchod_e($lang) ?>/kontakty">
                <?= frontend_velkoobchod_e(ui_text('markety.contact_button', 'Zobrazit kontakty')) ?>
                <span aria-hidden="true">›</span>
            </a>
        </section>
    </div>
</section>
