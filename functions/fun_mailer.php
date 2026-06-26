<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once ROOT_DIR . '/secure/lib/PHPMailer/src/Exception.php';
require_once ROOT_DIR . '/secure/lib/PHPMailer/src/PHPMailer.php';
require_once ROOT_DIR . '/secure/lib/PHPMailer/src/SMTP.php';

function mailer_config_value(array $config, string $key, ?string $default = null): ?string
{
    $value = trim((string)($config[$key] ?? ''));
    return $value !== '' ? $value : $default;
}

function mailer_config_int(array $config, string $key, int $default): int
{
    $value = (int)($config[$key] ?? 0);
    return $value > 0 ? $value : $default;
}

function mailer_default_port(array $config): int
{
    $server = strtolower((string)($config['smtp_server'] ?? ''));
    if (str_contains($server, 'gmail')) {
        return 587;
    }

    return 465;
}

function mailer_default_secure(int $port): string
{
    return $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
}

function mailer_config_bool(array $config, string $key, bool $default = false): bool
{
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

    return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
}

function mailer_email_list(mixed $emails): array
{
    if (is_array($emails)) {
        $parts = [];
        foreach ($emails as $email) {
            $parts = array_merge($parts, preg_split('/[,;\n\r]+/', (string)$email) ?: []);
        }
    } else {
        $parts = preg_split('/[,;\n\r]+/', (string)$emails) ?: [];
    }

    $normalized = [];
    foreach ($parts as $email) {
        $email = trim((string)$email);
        if ($email === '') {
            continue;
        }
        $key = strtolower($email);
        if (!isset($normalized[$key])) {
            $normalized[$key] = $email;
        }
    }

    return array_values($normalized);
}

function mailer_is_local_environment(): bool
{
    if (function_exists('is_local_environment')) {
        return (bool)is_local_environment();
    }

    $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? '');
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');

    if (
        str_contains($hostHeader, '.local')
        || in_array($remoteIp, ['127.0.0.1', '::1'], true)
        || in_array($serverAddr, ['127.0.0.1', '::1'], true)
    ) {
        return true;
    }

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        $hostName = (string)(gethostname() ?: php_uname('n'));
        return str_contains($hostName, '.local') || $hostName === 'localhost';
    }

    return false;
}

function mailer_is_sms_gateway_recipient(string $email, array $config): bool
{
    $domain = strtolower(trim((string)($config['mail_bypass_sms_domain'] ?? 'smsgate.sms-sluzba.cz')));
    $email = strtolower(trim($email));

    return $domain !== '' && str_ends_with($email, '@' . $domain);
}

function mailer_normalize_sms_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 9) {
        $digits = '420' . $digits;
    }

    return $digits;
}

function mailer_apply_local_bypass(array $config, array $message): array
{
    if (!mailer_is_local_environment()) {
        return $message;
    }
    if (!mailer_config_bool($config, 'mail_bypass_enabled', false)) {
        return $message;
    }

    $originalEmail = trim((string)($message['recipient_email'] ?? ''));
    if ($originalEmail === '') {
        return $message;
    }

    $isSmsGateway = mailer_is_sms_gateway_recipient($originalEmail, $config);
    if ($isSmsGateway) {
        $phone = mailer_normalize_sms_phone((string)($config['mail_bypass_sms_phone'] ?? ''));
        $domain = trim((string)($config['mail_bypass_sms_domain'] ?? 'smsgate.sms-sluzba.cz'));
        if ($phone !== '' && $domain !== '') {
            $message['recipient_email'] = $phone . '@' . $domain;
            $message['recipient_name'] = 'SMS brána TEST';
        }
    } else {
        $email = trim((string)($config['mail_bypass_email'] ?? ''));
        if ($email !== '') {
            $message['recipient_email'] = $email;
            $message['recipient_name'] = 'Lokální test';
        }
    }

    if (!$isSmsGateway && ($message['recipient_email'] ?? '') !== $originalEmail) {
        $subject = (string)($message['subject'] ?? '');
        $message['subject'] = '[LOCAL TEST -> ' . $originalEmail . '] ' . $subject;
        unset($message['cc_email'], $message['cc_emails'], $message['bcc_email'], $message['bcc_emails']);
        $note = "\n\n---\nLokální testovací přesměrování. Původní příjemce: " . $originalEmail;
        $message['body_text'] = (string)($message['body_text'] ?? '') . $note;
        if (trim((string)($message['body_html'] ?? '')) !== '') {
            $message['body_html'] = (string)$message['body_html'] .
                '<div style="margin-top:18px;padding:10px 12px;border:1px solid #f3d580;background:#fff4d7;color:#7a5a00;font:13px Arial,sans-serif;">' .
                '<strong>Lokální testovací přesměrování.</strong><br>Původní příjemce: ' .
                htmlspecialchars($originalEmail, ENT_QUOTES, 'UTF-8') .
                '</div>';
        }
    }

    return $message;
}

function mailer_send_smtp(array $config, array $message): ?string
{
    $host = mailer_config_value($config, 'smtp_server');
    $username = mailer_config_value($config, 'smtp_user');
    $password = mailer_config_value($config, 'smtp_password');
    $fromEmail = mailer_config_value($config, 'smtp_from');

    if ($host === null || $username === null || $password === null || $fromEmail === null) {
        throw new RuntimeException('Chybí SMTP nastavení v INI souboru.');
    }

    $port = mailer_config_int($config, 'smtp_port', mailer_default_port($config));
    $secure = mailer_config_value($config, 'smtp_secure', mailer_default_secure($port));
    $fromName = mailer_config_value($config, 'smtp_from_name', 'Qanto');
    $replyTo = mailer_config_value($config, 'smtp_reply_to');

    $message = mailer_apply_local_bypass($config, $message);
    $recipientEmails = mailer_email_list($message['recipient_email'] ?? '');
    if ($recipientEmails === []) {
        throw new InvalidArgumentException('E-mail příjemce nemá platný formát.');
    }
    foreach ($recipientEmails as $recipientEmail) {
        if (!PHPMailer::validateAddress($recipientEmail)) {
            throw new InvalidArgumentException('E-mail příjemce nemá platný formát.');
        }
    }

    $ccEmails = mailer_email_list($message['cc_emails'] ?? ($message['cc_email'] ?? ''));
    foreach ($ccEmails as $ccEmail) {
        if (!PHPMailer::validateAddress($ccEmail)) {
            throw new InvalidArgumentException('E-mail kopie nemá platný formát.');
        }
    }

    $bccEmails = mailer_email_list($message['bcc_emails'] ?? ($message['bcc_email'] ?? ''));
    foreach ($bccEmails as $bccEmail) {
        if (!PHPMailer::validateAddress($bccEmail)) {
            throw new InvalidArgumentException('E-mail skryté kopie nemá platný formát.');
        }
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port = $port;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, $fromName ?? '');
        if ($replyTo !== null && PHPMailer::validateAddress($replyTo)) {
            $mail->addReplyTo($replyTo);
        }
        foreach ($recipientEmails as $index => $recipientEmail) {
            $mail->addAddress($recipientEmail, $index === 0 ? (string)($message['recipient_name'] ?? '') : '');
        }
        foreach ($ccEmails as $ccEmail) {
            $mail->addCC($ccEmail);
        }
        foreach ($bccEmails as $bccEmail) {
            $mail->addBCC($bccEmail);
        }

        $attachments = $message['attachments'] ?? [];
        if (!is_array($attachments)) {
            $attachments = [];
        }
        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                $path = $attachment;
                $name = '';
            } elseif (is_array($attachment)) {
                $path = (string)($attachment['path'] ?? '');
                $name = (string)($attachment['name'] ?? '');
            } else {
                continue;
            }
            if ($path !== '' && is_file($path)) {
                $mail->addAttachment($path, $name !== '' ? $name : '');
            }
        }

        $mail->Subject = (string)($message['subject'] ?? '');
        $mail->isHTML(trim((string)($message['body_html'] ?? '')) !== '');
        if ($mail->ContentType === PHPMailer::CONTENT_TYPE_TEXT_HTML) {
            $mail->Body = (string)$message['body_html'];
            $mail->AltBody = (string)($message['body_text'] ?? '');
        } else {
            $mail->Body = (string)($message['body_text'] ?? '');
        }

        $mail->send();
    } catch (PHPMailerException $e) {
        throw new RuntimeException($mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage(), 0, $e);
    }

    return method_exists($mail, 'getLastMessageID') ? $mail->getLastMessageID() : null;
}

function mailer_log_recipient_label(array $message): string
{
    $recipients = mailer_email_list($message['recipient_email'] ?? '');
    $label = implode(', ', $recipients);
    if (mb_strlen($label) <= 190) {
        return $label;
    }

    $first = $recipients[0] ?? '';
    $remaining = max(0, count($recipients) - 1);
    return mb_substr($first . ($remaining > 0 ? ' +' . $remaining : ''), 0, 190);
}

function mailer_send_smtp_logged(PDO $pdo, array $config, array $message, array $logData = []): array
{
    require_once ROOT_DIR . '/functions/fun_email_log.php';

    $defaults = email_log_defaults_from_config($config);
    $payload = [
        'message' => $message,
        'recipients' => mailer_email_list($message['recipient_email'] ?? ''),
        'cc' => mailer_email_list($message['cc_emails'] ?? ($message['cc_email'] ?? '')),
        'bcc' => mailer_email_list($message['bcc_emails'] ?? ($message['bcc_email'] ?? '')),
    ];
    if (isset($logData['payload']) && is_array($logData['payload'])) {
        $payload = array_merge($payload, $logData['payload']);
    }
    $logData['payload'] = $payload;

    $logId = email_log_create($pdo, array_merge([
        'context' => 'system',
        'template_code' => null,
        'subject' => (string)($message['subject'] ?? ''),
        'recipient_email' => mailer_log_recipient_label($message),
        'recipient_name' => $message['recipient_name'] ?? null,
        'sender_email' => $defaults['sender_email'],
        'sender_name' => $defaults['sender_name'],
        'reply_to_email' => mailer_config_value($config, 'smtp_reply_to'),
        'provider' => $defaults['provider'],
        'payload' => $payload,
        'body_text' => $message['body_text'] ?? null,
        'body_html' => $message['body_html'] ?? null,
    ], $logData));

    try {
        $providerMessageId = mailer_send_smtp($config, $message);
        email_log_mark_sent($pdo, $logId, $providerMessageId);

        return [
            'ok' => true,
            'email_log_id' => $logId,
            'provider_message_id' => $providerMessageId,
        ];
    } catch (Throwable $e) {
        email_log_mark_failed($pdo, $logId, $e->getMessage());
        throw $e;
    }
}
