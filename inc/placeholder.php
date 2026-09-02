<?php
declare(strict_types=1);

$title = (string)($pagetitle ?? 'Qanto');
$title = preg_replace('~\s*\|\s*Qanto$~', '', $title) ?: $title;
?>
<section class="placeholder-section">
    <div class="site-shell placeholder-panel">
        <span class="eyebrow"><?= htmlspecialchars(ui_text('common.preparing'), ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(stat_vyraz_text('placeholder.text'), ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn-hero btn-hero--primary" href="/<?= htmlspecialchars((string)($lang ?? 'cz'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('common.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
