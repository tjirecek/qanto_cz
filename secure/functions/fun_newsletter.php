<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once ROOT_DIR . '/secure/lib/PHPMailer/src/Exception.php';
require_once ROOT_DIR . '/secure/lib/PHPMailer/src/PHPMailer.php';
require_once ROOT_DIR . '/secure/lib/PHPMailer/src/SMTP.php';

function newsletter_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function newsletter_config(): array
{
    return function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
}

function newsletter_config_string(string $key, string $default = ''): string
{
    $config = newsletter_config();
    $value = trim((string)($config[$key] ?? ''));

    return $value !== '' ? $value : $default;
}

function newsletter_config_bool(string $key, bool $default = false): bool
{
    $config = newsletter_config();
    if (!array_key_exists($key, $config)) {
        return $default;
    }

    $value = $config[$key];
    if (is_bool($value)) {
        return $value;
    }
    if (is_numeric($value)) {
        return (int)$value !== 0;
    }

    return in_array(mb_strtolower(trim((string)$value), 'UTF-8'), ['1', 'true', 'yes', 'on', 'ano'], true);
}

function newsletter_is_local_environment(): bool
{
    if (function_exists('app_is_local_environment')) {
        return app_is_local_environment();
    }

    $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

    return str_contains($hostHeader, '.local')
        || in_array($remoteIp, ['127.0.0.1', '::1'], true)
        || in_array($serverAddr, ['127.0.0.1', '::1'], true);
}

function newsletter_local_bypass_email(): string
{
    if (!newsletter_is_local_environment() || !newsletter_config_bool('mail_bypass_enabled', false)) {
        return '';
    }

    return newsletter_config_string('newsletter_local_test_email', newsletter_config_string('mail_bypass_email'));
}

function newsletter_is_local_bypass_enabled(): bool
{
    return newsletter_local_bypass_email() !== '';
}

function newsletter_public_base_url(): string
{
    $baseUrl = rtrim(newsletter_config_string('newsletter_public_base_url', 'https://www.qanto.cz'), '/');

    return $baseUrl !== '' ? $baseUrl : 'https://www.qanto.cz';
}

function newsletter_absolute_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || preg_match('~^(https?://|mailto:|tel:|#|data:)~i', $url) === 1) {
        return $url;
    }

    return newsletter_public_base_url() . '/' . ltrim($url, '/');
}

function newsletter_logo_url(): string
{
    return newsletter_absolute_url(newsletter_config_string('newsletter_logo_url', '/img/design/logo_admin_login.png'));
}

function newsletter_brand_name(): string
{
    return newsletter_config_string('newsletter_brand_name', 'Qanto');
}

function newsletter_accent_color(): string
{
    $color = newsletter_config_string('newsletter_accent_color', '#e30613');

    return preg_match('~^#[0-9a-f]{6}$~i', $color) === 1 ? $color : '#e30613';
}

function newsletter_news_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function newsletter_active_recipients(): array
{
    global $pdo;

    $stmt = $pdo->query('SELECT id, name, email FROM news_users WHERE valid = 1 AND registered = 1 ORDER BY id ASC');

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function newsletter_active_recipients_count(): int
{
    global $pdo;

    return (int)$pdo->query('SELECT COUNT(*) FROM news_users WHERE valid = 1 AND registered = 1')->fetchColumn();
}

function newsletter_delivery_recipients(): array
{
    $bypassEmail = newsletter_local_bypass_email();
    if ($bypassEmail !== '') {
        return [[
            'id' => 0,
            'name' => 'Lokální test',
            'email' => $bypassEmail,
        ]];
    }

    return newsletter_active_recipients();
}

function newsletter_delivery_recipients_count(): int
{
    return newsletter_is_local_bypass_enabled() ? 1 : newsletter_active_recipients_count();
}

function newsletter_subject(array $news): string
{
    $title = trim((string)($news['nazev_cz'] ?? ''));

    return 'Qanto novinky' . ($title !== '' ? ' :: ' . $title : '');
}

function newsletter_news_public_url(array $news): string
{
    $slug = trim((string)($news['url_cz'] ?? ''));
    if ($slug === '' || (int)($news['visible'] ?? 0) === 0) {
        return '';
    }

    $path = newsletter_config_string('newsletter_news_path', '/cz/index/news/');

    return newsletter_public_base_url() . '/' . trim($path, '/') . '/' . rawurlencode($slug);
}

function newsletter_absolute_html_urls(string $html): string
{
    $html = preg_replace_callback('~\b(src|href)=(["\'])(/[^"\']*)\2~i', static function (array $match): string {
        return $match[1] . '=' . $match[2] . newsletter_absolute_url($match[3]) . $match[2];
    }, $html) ?? $html;

    return preg_replace_callback('~\b(src|href)=(["\'])(?!https?://|mailto:|tel:|#|data:)([^"\']+)\2~i', static function (array $match): string {
        return $match[1] . '=' . $match[2] . newsletter_absolute_url($match[3]) . $match[2];
    }, $html) ?? $html;
}

function newsletter_prepare_content_html(string $html): string
{
    $html = newsletter_absolute_html_urls($html);

    return preg_replace_callback('~<img\b([^>]*)>~i', static function (array $match): string {
        $attributes = $match[1];
        $responsiveStyle = 'max-width:100%;height:auto;border:0;display:block;margin:18px auto;';

        if (preg_match('~\sstyle=(["\'])(.*?)\1~i', $attributes, $styleMatch) === 1) {
            $style = rtrim(trim($styleMatch[2]), ';');
            $style .= ($style !== '' ? ';' : '') . $responsiveStyle;
            $attributes = preg_replace('~\sstyle=(["\'])(.*?)\1~i', ' style="' . $style . '"', $attributes, 1) ?? $attributes;
        } else {
            $attributes .= ' style="' . $responsiveStyle . '"';
        }

        return '<img' . $attributes . '>';
    }, $html) ?? $html;
}

function newsletter_unsubscribe_token(int $userId, string $email): string
{
    $secret = newsletter_config_string('newsletter_unsubscribe_secret');
    if ($secret === '') {
        throw new RuntimeException('Chybí newsletter_unsubscribe_secret v INI konfiguraci.');
    }

    return hash_hmac('sha256', $userId . '|' . mb_strtolower(trim($email), 'UTF-8'), $secret);
}

function newsletter_unsubscribe_url(?array $recipient): string
{
    $url = newsletter_config_string('newsletter_unsubscribe_url', newsletter_public_base_url() . '/cz/newsletter/unsubscribe');
    if ($recipient === null) {
        return $url . (str_contains($url, '?') ? '&' : '?') . 'uid=preview&token=preview';
    }

    $userId = (int)($recipient['id'] ?? 0);
    $email = (string)($recipient['email'] ?? '');
    $separator = str_contains($url, '?') ? '&' : '?';

    return $url . $separator . 'uid=' . $userId . '&token=' . newsletter_unsubscribe_token($userId, $email);
}

function newsletter_body_html(array $news, ?array $recipient = null): string
{
    $title = trim((string)($news['nazev_cz'] ?? ''));
    $perex = trim((string)($news['perex_cz'] ?? ''));
    $text = trim((string)($news['text_cz'] ?? ''));
    $content = $text !== '' ? $text : $perex;
    $content = newsletter_prepare_content_html($content);
    $publicUrl = newsletter_news_public_url($news);
    $unsubscribeUrl = newsletter_unsubscribe_url($recipient);
    $logoUrl = newsletter_logo_url();
    $brandName = newsletter_brand_name();
    $accentColor = newsletter_accent_color();
    $year = date('Y');

    $viewLink = $publicUrl !== ''
        ? '<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0 0 0;"><tr><td bgcolor="' . newsletter_e($accentColor) . '" style="border-radius:999px;"><a href="' . newsletter_e($publicUrl) . '" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Zobrazit novinku na webu</a></td></tr></table>'
        : '';

    return '<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . newsletter_e($title !== '' ? $title : 'Qanto novinky') . '</title>
</head>
<body style="margin:0;padding:0;background:#eef1f4;color:#26323f;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;color:#eef1f4;font-size:1px;line-height:1px;">' . newsletter_e($title) . '</div>
  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef1f4;margin:0;padding:28px 0;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="720" style="width:720px;max-width:100%;background:#ffffff;border-collapse:collapse;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);">
          <tr>
            <td style="padding:28px 34px 22px 34px;background:#ffffff;border-top:8px solid ' . newsletter_e($accentColor) . ';">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img src="' . newsletter_e($logoUrl) . '" width="214" alt="' . newsletter_e($brandName) . '" style="display:block;width:214px;max-width:70%;height:auto;border:0;">
                  </td>
                  <td align="right" style="vertical-align:middle;color:#6b7280;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                    Newsletter
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#17212f;color:#ffffff;padding:34px 38px 36px 38px;">
              <div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">Novinky ' . newsletter_e($brandName) . '</div>
              <h1 style="margin:10px 0 0 0;font-size:32px;line-height:1.22;font-weight:800;">' . newsletter_e($title) . '</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 38px 38px 38px;font-size:16px;line-height:1.68;color:#26323f;">
              ' . $content . '
              ' . $viewLink . '
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:24px 38px;font-size:13px;line-height:1.55;color:#64748b;">
              <p style="margin:0 0 8px 0;">Tento e-mail dostáváte, protože jste přihlášený odběratel novinek ' . newsletter_e($brandName) . '.</p>
              <p style="margin:0 0 12px 0;"><a href="' . newsletter_e($unsubscribeUrl) . '" style="color:#64748b;text-decoration:underline;">Odhlásit odběr newsletteru</a></p>
              <p style="margin:0;">&copy; ' . newsletter_e($brandName) . ' :: Astur &amp; Qanto s.r.o. ' . $year . '</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function newsletter_body_text(array $news, ?array $recipient = null): string
{
    $title = trim((string)($news['nazev_cz'] ?? ''));
    $contentHtml = trim((string)($news['text_cz'] ?? ''));
    if ($contentHtml === '') {
        $contentHtml = trim((string)($news['perex_cz'] ?? ''));
    }
    $content = trim(strip_tags($contentHtml));
    $publicUrl = newsletter_news_public_url($news);
    $unsubscribeUrl = newsletter_unsubscribe_url($recipient);

    return trim($title . "\n\n" . $content . "\n\n" .
        ($publicUrl !== '' ? 'Novinka: ' . $publicUrl . "\n\n" : '') .
        'Odhlášení newsletteru: ' . $unsubscribeUrl);
}

function newsletter_email_for_mailer(string $email): string
{
    $email = mb_strtolower(trim($email), 'UTF-8');
    if (PHPMailer::validateAddress($email)) {
        return $email;
    }

    if (!function_exists('idn_to_ascii') || !str_contains($email, '@')) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);
    $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if (!is_string($asciiDomain) || $asciiDomain === '') {
        return $email;
    }

    return $local . '@' . $asciiDomain;
}

function newsletter_mailer(): PHPMailer
{
    $host = newsletter_config_string('klerk_smtp_host');
    $user = newsletter_config_string('klerk_smtp_user');
    $password = newsletter_config_string('klerk_smtp_password');
    $from = newsletter_config_string('klerk_smtp_from');

    if ($host === '' || $user === '' || $password === '' || $from === '') {
        throw new RuntimeException('Chybí Klerk SMTP nastavení v INI konfiguraci.');
    }

    $port = (int)newsletter_config_string('klerk_smtp_port', '465');
    $secure = newsletter_config_string('klerk_smtp_secure', PHPMailer::ENCRYPTION_SMTPS);
    $fromName = newsletter_config_string('klerk_smtp_from_name', 'Qanto');
    $campaignId = newsletter_config_string('klerk_campaign_id');

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $password;
    $mail->SMTPSecure = $secure;
    $mail->Port = $port;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($from, $fromName);

    if ($campaignId !== '') {
        $mail->addCustomHeader('X-CampaignID', $campaignId);
    }

    return $mail;
}

function newsletter_send_one(array $news, array $recipient): void
{
    $email = newsletter_email_for_mailer((string)($recipient['email'] ?? ''));
    if (!PHPMailer::validateAddress($email)) {
        throw new InvalidArgumentException('Neplatný e-mail příjemce: ' . $email);
    }

    $mail = newsletter_mailer();
    $mail->addAddress($email, trim((string)($recipient['name'] ?? '')));
    $mail->isHTML(true);
    $mail->Subject = (newsletter_is_local_bypass_enabled() ? '[LOCAL TEST] ' : '') . newsletter_subject($news);
    $mail->Body = newsletter_body_html($news, $recipient);
    $mail->AltBody = newsletter_body_text($news, $recipient);
    $mail->send();
}

function newsletter_send_campaign(int $newsId): array
{
    global $pdo;

    $news = newsletter_news_get($newsId);
    if (!is_array($news)) {
        throw new RuntimeException('Novinka nebyla nalezena.');
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
        'real_total' => newsletter_active_recipients_count(),
    ];

    foreach (newsletter_delivery_recipients() as $recipient) {
        $result['total']++;
        try {
            newsletter_send_one($news, $recipient);
            $result['sent']++;
        } catch (Throwable $e) {
            $result['failed']++;
            $result['errors'][] = trim((string)($recipient['email'] ?? '')) . ': ' . $e->getMessage();
        }
    }

    if ($result['sent'] > 0) {
        $stmt = $pdo->prepare('UPDATE news SET info_send = :info_send WHERE id = :id');
        $stmt->execute([
            ':id' => $newsId,
            ':info_send' => date('Y-m-d'),
        ]);
    }

    return $result;
}
