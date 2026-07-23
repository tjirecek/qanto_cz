<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_rep_akce_newsletter.php';

global $pdo;

$sendId = isset($_GET['send']) ? (int)$_GET['send'] : (int)($_POST['send'] ?? 0);
if ($sendId <= 0) {
    echo '<div class="alert alert-warning">Chybí parametr send.</div>';
    return;
}

if (!($pdo instanceof PDO)) {
    echo '<div class="alert alert-danger">PDO připojení není dostupné.</div>';
    return;
}

$csrfToken = (string)admin_session_get('rep_akce_send_csrf_token', '');
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(16));
    admin_session_set('rep_akce_send_csrf_token', $csrfToken);
}

$notice = '';
$error = '';
$sendResult = null;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
        }
        if (!in_array((int)admin_session_prava(), [1, 2], true)) {
            throw new RuntimeException('Nemáš oprávnění odesílat letáky.');
        }

        if ((string)($_POST['action'] ?? '') === 'send_akce_newsletter') {
            $sendResult = rep_akce_newsletter_send_campaign($pdo, $sendId);
            $notice = 'Leták byl odeslán: ' . (int)$sendResult['sent'] . ' úspěšně, '
                . (int)$sendResult['failed'] . ' chyb.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$offer = rep_akce_newsletter_offer($pdo, $sendId);
if (!is_array($offer)) {
    echo '<div class="alert alert-warning">Akční nabídka nebyla nalezena.</div>';
    return;
}

$recipientCount = rep_akce_newsletter_delivery_recipients_count($pdo, $offer);
$realRecipientCount = rep_akce_newsletter_active_recipients_count($pdo, $offer);
$localBypassEmail = newsletter_local_bypass_email();
$isLocalBypass = $localBypassEmail !== '';
$subject = rep_akce_newsletter_subject($offer);
$previewHtml = '';
$previewError = '';
$previewImage = rep_akce_newsletter_preview_image($pdo, $offer);
$offerUrl = rep_akce_newsletter_offer_url($offer);
$pdfUrl = rep_akce_newsletter_pdf_url($offer);

try {
    $previewHtml = rep_akce_newsletter_body_html($pdo, $offer);
} catch (Throwable $e) {
    $previewError = $e->getMessage();
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">Odeslání letáku</h1>
        <div class="text-muted small">Odeslání akční nabídky odběratelům proběhne až po potvrzení formuláře.</div>
    </div>
    <a href="index.php?section=02&amp;page=02&amp;sec_page=01" class="btn btn-sm btn-outline-secondary shadow-sm mt-3 mt-sm-0">
        <i class="bi bi-arrow-left me-1"></i> zpět na akce
    </a>
</div>

<?php if ($notice !== ''): ?>
    <div class="alert alert-success d-flex align-items-start" role="alert">
        <i class="bi bi-check-circle me-2 mt-1"></i>
        <div>
            <div><?= rep_akce_newsletter_e($notice) ?></div>
            <?php if (is_array($sendResult) && ($sendResult['errors'] ?? []) !== []): ?>
                <details class="mt-2">
                    <summary>Chyby odeslání</summary>
                    <ul class="mb-0 mt-2">
                        <?php foreach (array_slice((array)$sendResult['errors'], 0, 20) as $sendError): ?>
                            <li><?= rep_akce_newsletter_e($sendError) ?></li>
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
        <div><?= rep_akce_newsletter_e($error) ?></div>
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
                    <dt class="col-sm-4">ID letáku</dt>
                    <dd class="col-sm-8"><?= (int)$sendId ?></dd>

                    <dt class="col-sm-4">Název</dt>
                    <dd class="col-sm-8"><?= rep_akce_newsletter_e($offer['nazev_cz'] ?? '') ?></dd>

                    <dt class="col-sm-4">Typ</dt>
                    <dd class="col-sm-8"><?= rep_akce_newsletter_e($offer['typ_nazev_cz'] ?? 'bez typu') ?></dd>

                    <dt class="col-sm-4">Platnost</dt>
                    <dd class="col-sm-8"><?= rep_akce_newsletter_e(rep_akce_newsletter_validity_text($offer) ?: '-') ?></dd>

                    <dt class="col-sm-4">Předmět</dt>
                    <dd class="col-sm-8"><?= rep_akce_newsletter_e($subject) ?></dd>

                    <dt class="col-sm-4">Odkaz</dt>
                    <dd class="col-sm-8"><a href="<?= rep_akce_newsletter_e($offerUrl) ?>" target="_blank" rel="noopener"><?= rep_akce_newsletter_e($offerUrl) ?></a></dd>

                    <dt class="col-sm-4">PDF</dt>
                    <dd class="col-sm-8"><?= $pdfUrl !== '' ? '<a href="' . rep_akce_newsletter_e($pdfUrl) . '" target="_blank" rel="noopener">PDF ke stažení</a>' : '<span class="text-muted">není k dispozici</span>' ?></dd>

                    <dt class="col-sm-4">Náhled</dt>
                    <dd class="col-sm-8"><?= $previewImage !== '' ? '<a href="' . rep_akce_newsletter_e($previewImage) . '" target="_blank" rel="noopener">první strana / obálka</a>' : '<span class="text-muted">není k dispozici</span>' ?></dd>

                    <dt class="col-sm-4">Příjemci</dt>
                    <dd class="col-sm-8">
                        <?php if ($isLocalBypass): ?>
                            1 lokální testovací e-mail
                            <div class="text-muted small">Skutečných aktivních odběratelů pro tento typ v DB: <?= (int)$realRecipientCount ?></div>
                        <?php else: ?>
                            <?= (int)$recipientCount ?> aktivních odběratelů
                        <?php endif; ?>
                    </dd>
                </dl>

                <hr>

                <?php if ($isLocalBypass): ?>
                    <div class="alert alert-warning">
                        Lokální režim: leták se odešle pouze na
                        <strong><?= rep_akce_newsletter_e($localBypassEmail) ?></strong>.
                    </div>
                <?php endif; ?>

                <?php if ($previewError !== ''): ?>
                    <div class="alert alert-warning mb-0">
                        Náhled nelze sestavit: <?= rep_akce_newsletter_e($previewError) ?>
                    </div>
                <?php elseif ($recipientCount <= 0): ?>
                    <div class="alert alert-warning mb-0">
                        Leták nelze odeslat, protože pro jeho typ nejsou aktivní odběratelé.
                    </div>
                <?php else: ?>
                    <form method="post" data-rep-akce-confirm="Opravdu odeslat tento leták odběratelům?">
                        <input type="hidden" name="csrf_token" value="<?= rep_akce_newsletter_e($csrfToken) ?>">
                        <input type="hidden" name="action" value="send_akce_newsletter">
                        <input type="hidden" name="send" value="<?= (int)$sendId ?>">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send me-1"></i> <?= $isLocalBypass ? 'odeslat test letáku' : 'odeslat leták' ?>
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
                    <div><strong>Služba:</strong> Klerk SMTP, stejné nastavení jako newsletter novinek.</div>
                    <div><strong>Příjemci:</strong> aktivní odběratelé stejného typu letáku + historický odběr všech akcí.</div>
                    <div><strong>Deduplikace:</strong> stejná e-mailová adresa dostane leták jen jednou.</div>
                    <div><strong>Náhled:</strong> bere se první strana letáku, fallback je obálka.</div>
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
            <iframe class="newsletter-preview-frame" title="Náhled e-mailu letáku" srcdoc="<?= rep_akce_newsletter_e($previewHtml) ?>"></iframe>
        <?php else: ?>
            <div class="alert alert-warning mb-0">Náhled není k dispozici.</div>
        <?php endif; ?>
    </div>
</div>
