<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once SEC_DIR . '/functions/fun_rep_akce.php';
require_once SEC_DIR . '/functions/fun_newsletter.php';

function rep_akce_newsletter_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_akce_newsletter_offer(PDO $pdo, int $offerId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.*, t.nazev_cz AS typ_nazev_cz, t.nazev_en AS typ_nazev_en, t.code AS typ_code, t.legacy_id AS typ_legacy_id
         FROM rep_akce a
         LEFT JOIN rep_akce_typ t ON t.id = a.typ_id
         WHERE a.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $offerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function rep_akce_newsletter_date_label(mixed $date): string
{
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return '';
    }

    return function_exists('format_date_www') ? (string)format_date_www($date) : $date;
}

function rep_akce_newsletter_validity_text(array $offer): string
{
    $from = rep_akce_newsletter_date_label($offer['datum_od'] ?? '');
    $to = rep_akce_newsletter_date_label($offer['datum_do'] ?? '');

    if ($from !== '' && $to !== '') {
        return 'Platí od ' . $from . ' do ' . $to;
    }
    if ($to !== '') {
        return 'Platí do ' . $to;
    }
    if ($from !== '') {
        return 'Platí od ' . $from;
    }

    return '';
}

function rep_akce_newsletter_offer_url(array $offer): string
{
    return newsletter_public_base_url() . '/cz/akce?akce=' . (int)($offer['id'] ?? 0);
}

function rep_akce_newsletter_file_absolute_url(string $relativePath): string
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '' || !is_file(ROOT_DIR . '/' . $relativePath)) {
        return '';
    }

    return newsletter_absolute_url($relativePath);
}

function rep_akce_newsletter_preview_image(PDO $pdo, array $offer): string
{
    $stmt = $pdo->prepare('SELECT image_path FROM rep_akce_strany WHERE akce_id = :offer_id AND valid = 1 ORDER BY poradi ASC, id ASC LIMIT 1');
    $stmt->execute([':offer_id' => (int)($offer['id'] ?? 0)]);
    $firstPage = trim((string)($stmt->fetchColumn() ?: ''));
    if ($firstPage !== '') {
        $url = rep_akce_newsletter_file_absolute_url($firstPage);
        if ($url !== '') {
            return $url;
        }
    }

    $cover = rep_akce_primary_cover_path($offer);
    return rep_akce_newsletter_file_absolute_url($cover);
}

function rep_akce_newsletter_pdf_url(array $offer): string
{
    return rep_akce_newsletter_file_absolute_url(rep_akce_primary_pdf_path($offer));
}

function rep_akce_newsletter_subject(array $offer): string
{
    $title = trim((string)($offer['nazev_cz'] ?? ''));
    if ($title === '') {
        $title = trim((string)($offer['typ_nazev_cz'] ?? ''));
    }

    return 'Qanto leták' . ($title !== '' ? ' :: ' . $title : '');
}

function rep_akce_newsletter_active_recipients(PDO $pdo, array $offer): array
{
    $typeId = (int)($offer['typ_id'] ?? 0);
    $legacyTypeId = (int)($offer['typ_legacy_id'] ?? 0);

    $where = [
        'u.valid = 1',
        'u.registered = 1',
        '(u.datum_do IS NULL OR u.datum_do = "0000-00-00" OR u.datum_do >= CURDATE())',
    ];
    $params = [];

    if ($typeId > 0) {
        $where[] = '(u.akce_typ_id = :type_id OR u.akce_typ_id IS NULL OR u.akce_typ_id = 0 OR u.legacy_akce_typ = 0' . ($legacyTypeId > 0 ? ' OR u.legacy_akce_typ = :legacy_type_id' : '') . ')';
        $params[':type_id'] = $typeId;
        if ($legacyTypeId > 0) {
            $params[':legacy_type_id'] = $legacyTypeId;
        }
    }

    $sql = 'SELECT u.id, u.name, u.email
            FROM rep_akce_users u
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY u.id ASC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    $stmt->execute();

    $recipients = [];
    $seenEmails = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $emailKey = mb_strtolower(trim((string)($row['email'] ?? '')), 'UTF-8');
        if ($emailKey === '' || isset($seenEmails[$emailKey])) {
            continue;
        }
        $seenEmails[$emailKey] = true;
        $recipients[] = $row;
    }

    return $recipients;
}

function rep_akce_newsletter_active_recipients_count(PDO $pdo, array $offer): int
{
    return count(rep_akce_newsletter_active_recipients($pdo, $offer));
}

function rep_akce_newsletter_delivery_recipients(PDO $pdo, array $offer): array
{
    $bypassEmail = newsletter_local_bypass_email();
    if ($bypassEmail !== '') {
        return [[
            'id' => 0,
            'name' => 'Lokální test',
            'email' => $bypassEmail,
        ]];
    }

    return rep_akce_newsletter_active_recipients($pdo, $offer);
}

function rep_akce_newsletter_delivery_recipients_count(PDO $pdo, array $offer): int
{
    return newsletter_is_local_bypass_enabled() ? 1 : rep_akce_newsletter_active_recipients_count($pdo, $offer);
}

function rep_akce_newsletter_body_html(PDO $pdo, array $offer): string
{
    $title = trim((string)($offer['nazev_cz'] ?? ''));
    $typeLabel = trim((string)($offer['typ_nazev_cz'] ?? ''));
    $validity = rep_akce_newsletter_validity_text($offer);
    $offerUrl = rep_akce_newsletter_offer_url($offer);
    $pdfUrl = rep_akce_newsletter_pdf_url($offer);
    $previewImage = rep_akce_newsletter_preview_image($pdo, $offer);
    $logoUrl = newsletter_logo_url();
    $brandName = newsletter_brand_name();
    $accentColor = newsletter_accent_color();
    $year = date('Y');

    $subtitleParts = array_filter([$typeLabel, $validity], static fn(string $value): bool => $value !== '');
    $subtitle = implode(' · ', $subtitleParts);
    $downloadButton = $pdfUrl !== ''
        ? '<td bgcolor="#1d1d1b" style="border-radius:999px;"><a href="' . rep_akce_newsletter_e($pdfUrl) . '" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Stáhnout PDF</a></td>'
        : '';

    return '<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . rep_akce_newsletter_e($title !== '' ? $title : 'Qanto leták') . '</title>
</head>
<body style="margin:0;padding:0;background:#eef1f4;color:#26323f;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;color:#eef1f4;font-size:1px;line-height:1px;">' . rep_akce_newsletter_e($subtitle) . '</div>
  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef1f4;margin:0;padding:28px 0;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="720" style="width:720px;max-width:100%;background:#ffffff;border-collapse:collapse;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);">
          <tr>
            <td style="padding:28px 34px 22px 34px;background:#ffffff;border-top:8px solid ' . rep_akce_newsletter_e($accentColor) . ';">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img src="' . rep_akce_newsletter_e($logoUrl) . '" width="214" alt="' . rep_akce_newsletter_e($brandName) . '" style="display:block;width:214px;max-width:70%;height:auto;border:0;">
                  </td>
                  <td align="right" style="vertical-align:middle;color:#6b7280;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                    Leták
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#17212f;color:#ffffff;padding:34px 38px 36px 38px;">
              <div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">Akční nabídka ' . rep_akce_newsletter_e($brandName) . '</div>
              <h1 style="margin:10px 0 0 0;font-size:32px;line-height:1.22;font-weight:800;">' . rep_akce_newsletter_e($title) . '</h1>
              ' . ($subtitle !== '' ? '<p style="margin:14px 0 0 0;color:#d8dee8;font-size:16px;line-height:1.5;">' . rep_akce_newsletter_e($subtitle) . '</p>' : '') . '
            </td>
          </tr>
          <tr>
            <td style="padding:34px 38px 38px 38px;font-size:16px;line-height:1.68;color:#26323f;">
              ' . ($previewImage !== '' ? '<a href="' . rep_akce_newsletter_e($offerUrl) . '" style="display:block;text-decoration:none;"><img src="' . rep_akce_newsletter_e($previewImage) . '" alt="' . rep_akce_newsletter_e($title) . '" style="display:block;width:100%;max-width:520px;height:auto;margin:0 auto 26px auto;border:0;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.16);"></a>' : '') . '
              <p style="margin:0 0 20px 0;">Nový leták najdete na webu qanto.cz. Odkaz níže otevře detail letáku včetně prohlížení stran.</p>
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0 0 0;"><tr>
                <td bgcolor="' . rep_akce_newsletter_e($accentColor) . '" style="border-radius:999px;"><a href="' . rep_akce_newsletter_e($offerUrl) . '" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Prohlédnout leták</a></td>
                <td style="width:12px;"></td>
                ' . $downloadButton . '
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:24px 38px;font-size:13px;line-height:1.55;color:#64748b;">
              <p style="margin:0 0 8px 0;">Tento e-mail dostáváte, protože jste přihlášený odběratel akčních nabídek ' . rep_akce_newsletter_e($brandName) . '.</p>
              <p style="margin:0;">&copy; ' . rep_akce_newsletter_e($brandName) . ' :: Astur &amp; Qanto s.r.o. ' . $year . '</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function rep_akce_newsletter_body_text(PDO $pdo, array $offer): string
{
    $title = trim((string)($offer['nazev_cz'] ?? ''));
    $validity = rep_akce_newsletter_validity_text($offer);
    $offerUrl = rep_akce_newsletter_offer_url($offer);
    $pdfUrl = rep_akce_newsletter_pdf_url($offer);

    return trim($title . "\n" .
        ($validity !== '' ? $validity . "\n\n" : "\n") .
        'Leták: ' . $offerUrl . "\n" .
        ($pdfUrl !== '' ? 'PDF: ' . $pdfUrl . "\n" : ''));
}

function rep_akce_newsletter_send_one(PDO $pdo, array $offer, array $recipient): void
{
    $email = newsletter_email_for_mailer((string)($recipient['email'] ?? ''));
    if (!PHPMailer::validateAddress($email)) {
        throw new InvalidArgumentException('Neplatný e-mail příjemce: ' . $email);
    }

    $mail = newsletter_mailer();
    $mail->addAddress($email, trim((string)($recipient['name'] ?? '')));
    $mail->isHTML(true);
    $mail->Subject = (newsletter_is_local_bypass_enabled() ? '[LOCAL TEST] ' : '') . rep_akce_newsletter_subject($offer);
    $mail->Body = rep_akce_newsletter_body_html($pdo, $offer);
    $mail->AltBody = rep_akce_newsletter_body_text($pdo, $offer);
    $mail->send();
}

function rep_akce_newsletter_send_campaign(PDO $pdo, int $offerId): array
{
    $offer = rep_akce_newsletter_offer($pdo, $offerId);
    if (!is_array($offer)) {
        throw new RuntimeException('Akční nabídka nebyla nalezena.');
    }

    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }

    $result = [
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'errors' => [],
        'local_bypass' => newsletter_is_local_bypass_enabled(),
        'real_total' => rep_akce_newsletter_active_recipients_count($pdo, $offer),
    ];

    foreach (rep_akce_newsletter_delivery_recipients($pdo, $offer) as $recipient) {
        $result['total']++;
        try {
            rep_akce_newsletter_send_one($pdo, $offer, $recipient);
            $result['sent']++;
        } catch (Throwable $e) {
            $result['failed']++;
            $result['errors'][] = trim((string)($recipient['email'] ?? '')) . ': ' . $e->getMessage();
        }
    }

    return $result;
}
