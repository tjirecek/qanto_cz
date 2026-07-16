<?php
declare(strict_types=1);

$title = (string)($pagetitle ?? 'Qanto');
$title = preg_replace('~\s*\|\s*Qanto$~', '', $title) ?: $title;
?>
<section class="placeholder-section">
    <div class="site-shell placeholder-panel">
        <span class="eyebrow"><?= htmlspecialchars(ui_text('common.preparing', 'Připravujeme'), ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(ui_text('placeholder.text', 'Tato část frontendu bude napojená na hotovou administraci a doladíme ji podle Figmy.'), ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn-hero btn-hero--primary" href="/<?= htmlspecialchars((string)($lang ?? 'cz'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ui_text('common.back_home', 'Zpět na úvod'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
