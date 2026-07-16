<?php
declare(strict_types=1);

$detailSidebarItems = is_array($detailSidebarItems ?? null) ? $detailSidebarItems : [];
?>
<?php if ($detailSidebarItems !== []): ?>
    <aside class="detail-sidebar" aria-label="<?= htmlspecialchars(ui_text('detail.sidebar_links', 'Doporučené odkazy'), ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($detailSidebarItems as $ad): ?>
            <?php
            $hasImage = (string)($ad['image'] ?? '') !== '';
            $hasCoverImage = $hasImage && (string)($ad['image_mode'] ?? '') === 'cover';
            $themeClass = 'steady-ad-card--' . preg_replace('~[^a-z0-9_-]+~i', '', $hasCoverImage ? 'custom' : (string)($ad['theme'] ?? 'brand-red'));
            $textColorClass = (string)($ad['text_color'] ?? '') === 'light' ? 'steady-ad-card--light' : 'steady-ad-card--dark';
            $imageClass = $hasCoverImage ? 'steady-ad-card--has-image' : '';
            $linkText = trim((string)($ad['link_text'] ?? ''));
            if ($linkText === '') {
                $linkText = ui_text('common.more', 'Zjistěte více');
            }
            ?>
            <a class="steady-ad-card detail-sidebar__card <?= htmlspecialchars(trim($themeClass . ' ' . $textColorClass . ' ' . $imageClass), ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string)($ad['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($hasImage): ?>
                    <img src="<?= htmlspecialchars((string)$ad['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="steady-ad-card__image" loading="lazy">
                <?php endif; ?>
                <span class="steady-ad-card__content">
                    <strong><?= htmlspecialchars((string)($ad['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </aside>
<?php endif; ?>
