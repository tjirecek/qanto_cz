<?php
declare(strict_types=1);

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$userIdRaw = trim((string)($_POST['uid'] ?? $_GET['uid'] ?? ''));
$token = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$isPreview = $userIdRaw === 'preview' && $token === 'preview';
$recipient = $isPreview ? null : rep_akce_unsubscribe_recipient($pdo, (int)$userIdRaw, $token);
$unsubscribeDone = false;
$unsubscribeCount = 0;

if ($requestMethod === 'POST' && is_array($recipient)) {
    $unsubscribeCount = rep_akce_unsubscribe_all_by_email($pdo, (string)$recipient['email']);
    $unsubscribeDone = true;
}
?>
<section class="placeholder-section">
    <div class="site-shell placeholder-panel">
        <span class="eyebrow"><?= htmlspecialchars(ui_text('unsubscribe.kicker'), ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars(ui_text('unsubscribe.title'), ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($isPreview): ?>
            <div class="alert alert-info mb-4" role="status">
                <?= htmlspecialchars(ui_text('unsubscribe.preview'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php elseif (!is_array($recipient)): ?>
            <div class="alert alert-danger mb-4" role="alert">
                <?= htmlspecialchars(ui_text('unsubscribe.invalid'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php elseif ($unsubscribeDone): ?>
            <div class="alert alert-success mb-4" role="status">
                <?= htmlspecialchars(sprintf(ui_text('unsubscribe.done'), rep_akce_unsubscribe_mask_email((string)$recipient['email'])), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php if ($unsubscribeCount === 0): ?>
                <p class="text-muted"><?= htmlspecialchars(ui_text('unsubscribe.no_active'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php elseif ((int)($recipient['registered'] ?? 0) !== 1): ?>
            <div class="alert alert-info mb-4" role="status">
                <?= htmlspecialchars(sprintf(ui_text('unsubscribe.already_done'), rep_akce_unsubscribe_mask_email((string)$recipient['email'])), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <p><?= htmlspecialchars(sprintf(ui_text('unsubscribe.confirm'), rep_akce_unsubscribe_mask_email((string)$recipient['email'])), ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="mt-4">
                <input type="hidden" name="uid" value="<?= (int)$recipient['id'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-danger"><?= htmlspecialchars(ui_text('unsubscribe.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        <?php endif; ?>

        <a class="btn-hero btn-hero--primary mt-4" href="/<?= htmlspecialchars((string)($lang ?? 'cz'), ENT_QUOTES, 'UTF-8') ?>/akce"><?= htmlspecialchars(ui_text('unsubscribe.back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
