<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$parts = array_values(array_filter(explode('/', trim($path, '/'))));
$slug = '';
if (($parts[0] ?? '') === $lang && ($parts[1] ?? '') === 'news' && isset($parts[2])) {
    $slug = rawurldecode((string)$parts[2]);
}

$subscribeResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && (string)($_POST['action'] ?? '') === 'news_subscribe'
    && function_exists('frontend_news_subscribe_save')
) {
    $subscribeResult = frontend_news_subscribe_save($_POST);
}
$subscribeToken = function_exists('frontend_news_subscribe_token') ? frontend_news_subscribe_token() : '';

$detail = $slug !== '' && function_exists('frontend_news_detail_row') ? frontend_news_detail_row($lang, $slug) : null;
$detailSidebarItems = $detail && function_exists('frontend_detail_sidebar_ads') ? frontend_detail_sidebar_ads($lang, 3) : [];
$tagOptions = (!$detail && function_exists('frontend_news_tags')) ? frontend_news_tags($lang) : [];
$selectedTag = trim((string)($_GET['tag'] ?? ''));
if ($selectedTag !== '' && preg_match('~^[a-z0-9_-]+$~i', $selectedTag) !== 1) {
    $selectedTag = '';
}
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 13;
$totalItems = (!$detail && function_exists('frontend_news_count')) ? frontend_news_count($lang, $selectedTag !== '' ? $selectedTag : null) : 0;
$totalPages = max(1, (int)ceil($totalItems / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
$items = $detail ? [] : (function_exists('frontend_news_rows') ? frontend_news_rows($lang, $perPage, null, $offset, $selectedTag !== '' ? $selectedTag : null) : []);
$featuredItem = $currentPage === 1 ? ($items[0] ?? null) : null;
$listItems = $featuredItem ? array_slice($items, 1) : $items;
$newsListUrl = static function (?string $tag = null, int $pageNumber = 1) use ($lang): string {
    $query = [];
    $tag = trim((string)$tag);
    if ($tag !== '') {
        $query['tag'] = $tag;
    }
    if ($pageNumber > 1) {
        $query['p'] = $pageNumber;
    }

    return '/' . rawurlencode($lang) . '/news' . ($query !== [] ? '?' . http_build_query($query) : '');
};
?>

<section class="news-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= htmlspecialchars(ui_text('aria.breadcrumb', 'Drobečková navigace'), ENT_QUOTES, 'UTF-8') ?>">
            <ol>
                <li>
                    <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.home', 'Domů'), ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li>
                    <?php if ($detail): ?>
                        <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/news"><?= htmlspecialchars(ui_text('news.page_title', 'Novinky'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= htmlspecialchars(ui_text('news.page_title', 'Novinky'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </li>
                <?php if ($detail): ?>
                    <li><span aria-current="page"><?= htmlspecialchars((string)$detail['title'], ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php endif; ?>
            </ol>
        </nav>

        <?php if ($detail): ?>
            <div class="detail-page-layout">
                <article class="news-detail detail-page-content">
                    <div class="news-detail__hero<?= (string)($detail['detail_image'] ?? '') === '' ? ' news-detail__hero--no-image' : '' ?>">
                        <div class="news-detail__intro">
                            <time><?= htmlspecialchars((string)$detail['date'], ENT_QUOTES, 'UTF-8') ?></time>
                            <h1><?= htmlspecialchars((string)$detail['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                            <?php if (!empty($detail['tags'])): ?>
                                <div class="news-tags">
                                    <?php foreach ($detail['tags'] as $tag): ?>
                                        <span class="news-tag <?= htmlspecialchars((string)$tag['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ((string)($detail['perex_full'] ?? '') !== ''): ?>
                                <p class="news-detail__perex"><?= htmlspecialchars((string)$detail['perex_full'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ((string)($detail['detail_image'] ?? '') !== ''): ?>
                            <img src="<?= htmlspecialchars((string)$detail['detail_image'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="news-detail__image">
                        <?php endif; ?>
                    </div>
                    <div class="news-detail__content">
                        <?= (string)($detail['content'] ?: '<p>' . htmlspecialchars((string)$detail['perex'], ENT_QUOTES, 'UTF-8') . '</p>') ?>
                    </div>
                </article>
                <?php include __DIR__ . '/detail_sidebar.php'; ?>
            </div>
        <?php else: ?>
            <div class="news-page__head">
                <h1><?= htmlspecialchars(ui_text('news.page_title', 'Novinky'), ENT_QUOTES, 'UTF-8') ?></h1>
            </div>

            <?php if ($tagOptions !== []): ?>
                <nav class="news-filter" aria-label="<?= htmlspecialchars(ui_text('news.tags_filter', 'Filtrovat podle štítků'), ENT_QUOTES, 'UTF-8') ?>">
                    <a class="news-filter__item<?= $selectedTag === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($newsListUrl(null), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(ui_text('news.tags_all', 'Všechny'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php foreach ($tagOptions as $tag): ?>
                        <?php $tagSlug = (string)$tag['slug']; ?>
                        <a class="news-filter__item <?= htmlspecialchars((string)$tag['class'], ENT_QUOTES, 'UTF-8') ?><?= $selectedTag === $tagSlug ? ' is-active' : '' ?>" href="<?= htmlspecialchars($newsListUrl($tagSlug), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$tag['label'], ENT_QUOTES, 'UTF-8') ?>
                            <small><?= (int)$tag['count'] ?></small>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if ($featuredItem): ?>
                <div class="news-page__top">
                    <a class="news-page-featured" href="<?= htmlspecialchars((string)$featuredItem['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ((string)($featuredItem['image'] ?? '') !== ''): ?>
                            <span class="news-page-featured__image">
                                <img src="<?= htmlspecialchars((string)$featuredItem['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="eager">
                            </span>
                        <?php endif; ?>
                        <span class="news-page-featured__body">
                            <time><?= htmlspecialchars((string)$featuredItem['date'], ENT_QUOTES, 'UTF-8') ?></time>
                            <strong><?= htmlspecialchars((string)$featuredItem['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="news-card__text"><?= htmlspecialchars((string)$featuredItem['perex'], ENT_QUOTES, 'UTF-8') ?> <em><?= htmlspecialchars(ui_text('news.read_more', 'přečíst celé'), ENT_QUOTES, 'UTF-8') ?></em></span>
                            <?php if (!empty($featuredItem['tags'])): ?>
                                <span class="news-tags">
                                    <?php foreach ($featuredItem['tags'] as $tag): ?>
                                        <span class="news-tag <?= htmlspecialchars((string)$tag['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>

                    <aside class="news-subscribe" aria-labelledby="news-subscribe-title">
                        <span><?= htmlspecialchars(ui_text('news.subscribe_kicker', 'Odběr novinek'), ENT_QUOTES, 'UTF-8') ?></span>
                        <h2 id="news-subscribe-title"><?= htmlspecialchars(ui_text('news.subscribe_title', 'Novinky na e-mail'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars(ui_text('news.subscribe_text', 'Pošleme vám aktuální informace ze světa Qanto.'), ENT_QUOTES, 'UTF-8') ?></p>

                        <?php if (is_array($subscribeResult)): ?>
                            <div class="news-subscribe__message <?= (bool)($subscribeResult['ok'] ?? false) ? 'is-success' : 'is-error' ?>" role="status">
                                <?= htmlspecialchars((string)($subscribeResult['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="news-subscribe__form">
                            <input type="hidden" name="action" value="news_subscribe">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($subscribeToken, ENT_QUOTES, 'UTF-8') ?>">
                            <label class="visually-hidden" for="news_subscribe_email"><?= htmlspecialchars(ui_text('news.subscribe_email', 'Váš e-mail'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="email"
                                   name="email"
                                   id="news_subscribe_email"
                                   required
                                   autocomplete="email"
                                   placeholder="<?= htmlspecialchars(ui_text('news.subscribe_email', 'Váš e-mail'), ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= is_array($subscribeResult) && !(bool)($subscribeResult['ok'] ?? false) ? htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                            <?php if (function_exists('frontend_captcha_render')): ?>
                                <?php frontend_captcha_render('news_subscribe', 'news-subscribe'); ?>
                            <?php endif; ?>
                            <button type="submit"><?= htmlspecialchars(ui_text('news.subscribe_button', 'Odebírat'), ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">›</span></button>
                        </form>

                        <p class="news-subscribe__consent">
                            <?= htmlspecialchars(ui_text('news.subscribe_consent', 'Odběrem souhlasíte se'), ENT_QUOTES, 'UTF-8') ?>
                            <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/osobni-udaje">
                                <?= htmlspecialchars(ui_text('news.subscribe_privacy_link', 'zpracováním osobních údajů'), ENT_QUOTES, 'UTF-8') ?>
                            </a>.
                        </p>
                    </aside>
                </div>
            <?php endif; ?>

            <?php if ($listItems !== []): ?>
                <div class="news-page__subhead">
                    <span><?= htmlspecialchars(ui_text('news.more_title', 'Další novinky'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="news-page__grid">
                    <?php foreach ($listItems as $item): ?>
                        <a class="news-list-card" href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ((string)($item['image'] ?? '') !== ''): ?>
                                <img src="<?= htmlspecialchars((string)$item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <span class="news-list-card__body">
                                <time><?= htmlspecialchars((string)$item['date'], ENT_QUOTES, 'UTF-8') ?></time>
                                <strong><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars((string)$item['perex'], ENT_QUOTES, 'UTF-8') ?></span>
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
            <?php elseif (!$featuredItem): ?>
                <div class="home-news__empty"><?= htmlspecialchars(ui_text('news.empty', 'Aktuálně zde nejsou žádné novinky.'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                    $endPage = min($totalPages, $startPage + 4);
                }
                ?>
                <nav class="news-pagination" aria-label="<?= htmlspecialchars(ui_text('news.pagination_label', 'Stránkování novinek'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= htmlspecialchars($newsListUrl($selectedTag, 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('news.pagination_first', 'První stránka'), ENT_QUOTES, 'UTF-8') ?>">‹‹</a>
                        <a href="<?= htmlspecialchars($newsListUrl($selectedTag, $currentPage - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('news.pagination_prev', 'Předchozí stránka'), ENT_QUOTES, 'UTF-8') ?>">‹</a>
                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a class="<?= $i === $currentPage ? 'is-active' : '' ?>" href="<?= htmlspecialchars($newsListUrl($selectedTag, $i), ENT_QUOTES, 'UTF-8') ?>"<?= $i === $currentPage ? ' aria-current="page"' : '' ?>>
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= htmlspecialchars($newsListUrl($selectedTag, $currentPage + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('news.pagination_next', 'Další stránka'), ENT_QUOTES, 'UTF-8') ?>">›</a>
                        <a href="<?= htmlspecialchars($newsListUrl($selectedTag, $totalPages), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('news.pagination_last', 'Poslední stránka'), ENT_QUOTES, 'UTF-8') ?>">››</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
