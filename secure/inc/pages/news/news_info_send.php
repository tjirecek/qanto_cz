<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_newsletter.php';

$sendId = isset($_GET['send']) ? (int)$_GET['send'] : (int)($_POST['send'] ?? 0);
if ($sendId <= 0) {
    echo '<div class="alert alert-warning">Chybí parametr send.</div>';
    return;
}

$csrfToken = (string)admin_session_get('news_send_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('news_send_csrf_token', $csrfToken);
}

$notice = '';
$error = '';
$sendResult = null;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }

        if ((string)($_POST['action'] ?? '') === 'send_newsletter') {
            $sendResult = newsletter_send_campaign($sendId);
            $notice = 'Newsletter byl odeslán: ' . (int)$sendResult['sent'] . ' úspěšně, '
                . (int)$sendResult['failed'] . ' chyb.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$news = newsletter_news_get($sendId);
if (!is_array($news)) {
    echo '<div class="alert alert-warning">Novinka nebyla nalezena.</div>';
    return;
}

$recipientCount = newsletter_delivery_recipients_count();
$realRecipientCount = newsletter_active_recipients_count();
$localBypassEmail = newsletter_local_bypass_email();
$isLocalBypass = $localBypassEmail !== '';
$subject = newsletter_subject($news);
$previewHtml = '';
$previewError = '';

try {
    $previewHtml = newsletter_body_html($news, null);
} catch (Throwable $e) {
    $previewError = $e->getMessage();
}

$infoSend = (string)($news['info_send'] ?? '');
$infoSendText = ($infoSend === '' || $infoSend === '0000-00-00') ? 'NE' : format_date_www($infoSend);
$visibleText = match ((int)($news['visible'] ?? 0)) {
    1 => 'CZ/EN',
    2 => 'CZ',
    3 => 'EN',
    default => 'NE / pouze administrace',
};
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Náhled newsletteru</h1>
        <div class="text-muted small">Odeslání novinky přes Klerk proběhne až po potvrzení formuláře.</div>
    </div>
    <a href="index.php?section=01&amp;page=01&amp;sec_page=02" class="btn btn-sm btn-outline-secondary shadow-sm mt-3 mt-sm-0">
        <i class="bi bi-arrow-left me-1"></i> zpět na novinky
    </a>
</div>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success d-flex align-items-start" role="alert">
        <i class="bi bi-check-circle me-2 mt-1"></i>
        <div>
            <div><?= newsletter_e($notice) ?></div>
            <?php if (is_array($sendResult) && ($sendResult['errors'] ?? []) !== []): ?>
                <details class="mt-2">
                    <summary>Chyby odeslání</summary>
                    <ul class="mb-0 mt-2">
                        <?php foreach (array_slice((array)$sendResult['errors'], 0, 20) as $sendError): ?>
                            <li><?= newsletter_e($sendError) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div><?= newsletter_e($error) ?></div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Rekapitulace odeslání</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">ID novinky</dt>
                    <dd class="col-sm-8"><?= (int)$sendId ?></dd>

                    <dt class="col-sm-4">Název</dt>
                    <dd class="col-sm-8"><?= newsletter_e($news['nazev_cz'] ?? '') ?></dd>

                    <dt class="col-sm-4">Předmět</dt>
                    <dd class="col-sm-8"><?= newsletter_e($subject) ?></dd>

                    <dt class="col-sm-4">Zobrazení</dt>
                    <dd class="col-sm-8"><span class="badge text-bg-primary"><?= newsletter_e($visibleText) ?></span></dd>

                    <dt class="col-sm-4">Odesláno</dt>
                    <dd class="col-sm-8"><?= newsletter_e($infoSendText) ?></dd>

                    <dt class="col-sm-4">Příjemci</dt>
                    <dd class="col-sm-8">
                        <?php if ($isLocalBypass): ?>
                            1 lokální testovací e-mail
                            <div class="text-muted small">Skutečných aktivních odběratelů v DB: <?= (int)$realRecipientCount ?></div>
                        <?php else: ?>
                            <?= (int)$recipientCount ?> aktivních odběratelů
                        <?php endif; ?>
                    </dd>
                </dl>

                <hr>

                <?php if ($isLocalBypass): ?>
                    <div class="alert alert-warning">
                        Lokální režim: newsletter se odešle pouze na
                        <strong><?= newsletter_e($localBypassEmail) ?></strong>.
                    </div>
                <?php endif; ?>

                <?php if ($previewError !== ''): ?>
                    <div class="alert alert-warning mb-0">
                        Náhled nelze sestavit: <?= newsletter_e($previewError) ?>
                    </div>
                <?php elseif ($recipientCount <= 0): ?>
                    <div class="alert alert-warning mb-0">
                        Newsletter nelze odeslat, protože nejsou aktivní odběratelé.
                    </div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= newsletter_e($csrfToken) ?>">
                        <input type="hidden" name="action" value="send_newsletter">
                        <input type="hidden" name="send" value="<?= (int)$sendId ?>">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send me-1"></i> <?= $isLocalBypass ? 'odeslat test newsletteru' : 'odeslat newsletter' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Technické nastavení</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <div><strong>Služba:</strong> Klerk SMTP</div>
                    <div><strong>Hlavička kampaně:</strong> <code>X-CampaignID</code> z INI konfigurace</div>
                    <div><strong>Odhlášení:</strong> každý příjemce dostane unikátní odkaz s <code>uid</code> a tokenem.</div>
                    <div><strong>Skrytá novinka:</strong> položka se zobrazením „pouze administrace“ se může také odeslat.</div>
                    <div><strong>Lokál:</strong> při zapnutém <code>mail_bypass_enabled</code> se posílá jen na testovací e-mail.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Náhled e-mailu</h6>
    </div>
    <div class="card-body">
        <?php if ($previewHtml !== ''): ?>
            <iframe class="newsletter-preview-frame" title="Náhled newsletteru" srcdoc="<?= newsletter_e($previewHtml) ?>"></iframe>
        <?php else: ?>
            <div class="alert alert-warning mb-0">Náhled není k dispozici.</div>
        <?php endif; ?>
    </div>
</div>
