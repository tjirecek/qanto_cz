<?php
declare(strict_types=1);

function frontend_tenis_qcup_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_tenis_qcup_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $token = $_SESSION['frontend_tenis_qcup_csrf'] ?? '';
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(24));
        $_SESSION['frontend_tenis_qcup_csrf'] = $token;
    }

    return $token;
}

function frontend_tenis_qcup_clean(mixed $value, int $limit = 255): string
{
    $value = trim((string)$value);
    $value = preg_replace("~\r\n?~", "\n", $value) ?? $value;
    $value = preg_replace('~[^\P{C}\n\t]+~u', '', $value) ?? $value;

    return mb_strlen($value, 'UTF-8') > $limit
        ? mb_substr($value, 0, $limit, 'UTF-8')
        : $value;
}

function frontend_tenis_qcup_setting_number(string $name, int $default): int
{
    $value = function_exists('sp_hodnota') ? sp_hodnota($name) : null;

    return $value !== null && is_numeric($value) ? (int)$value : $default;
}

function frontend_tenis_qcup_setting_text(string $name, string $default = ''): string
{
    $value = function_exists('sp_hodnota_text') ? trim((string)sp_hodnota_text($name)) : '';

    return $value !== '' ? $value : $default;
}

function frontend_tenis_qcup_enabled(): bool
{
    return frontend_tenis_qcup_setting_number('tenis_registrace-on', 0) === 1;
}

function frontend_tenis_qcup_year(): int
{
    $year = frontend_tenis_qcup_setting_number('tenis_default-year', (int)date('Y'));

    return $year >= 2000 && $year <= 2100 ? $year : (int)date('Y');
}

function frontend_tenis_qcup_public_url(string $path = ''): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $baseUrl = rtrim(trim((string)($config['newsletter_public_base_url'] ?? 'https://www.qanto.cz')), '/');

    return ($baseUrl !== '' ? $baseUrl : 'https://www.qanto.cz') . '/' . ltrim($path, '/');
}

function frontend_tenis_qcup_logo_url(): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $logo = trim((string)($config['newsletter_logo_url'] ?? '/img/design/logo_admin_login.png'));

    return preg_match('~^https?://~i', $logo) === 1
        ? $logo
        : frontend_tenis_qcup_public_url($logo);
}

function frontend_tenis_qcup_accent_color(): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $color = trim((string)($config['newsletter_accent_color'] ?? '#e30613'));

    return preg_match('~^#[0-9a-f]{6}$~i', $color) === 1 ? $color : '#e30613';
}

function frontend_tenis_qcup_email_layout(string $kicker, string $title, string $content): string
{
    $accent = frontend_tenis_qcup_accent_color();
    $logo = frontend_tenis_qcup_logo_url();

    return '<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . frontend_tenis_qcup_e($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#eef1f4;color:#26323f;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef1f4;margin:0;padding:28px 0;">
    <tr><td align="center">
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="720" style="width:720px;max-width:100%;background:#ffffff;border-collapse:collapse;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);">
        <tr><td style="padding:28px 34px 22px;background:#ffffff;border-top:8px solid ' . frontend_tenis_qcup_e($accent) . ';">
          <img src="' . frontend_tenis_qcup_e($logo) . '" width="214" alt="Qanto" style="display:block;width:214px;max-width:70%;height:auto;border:0;">
        </td></tr>
        <tr><td style="background:#17212f;color:#ffffff;padding:34px 38px 36px;">
          <div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">' . frontend_tenis_qcup_e($kicker) . '</div>
          <h1 style="margin:10px 0 0;font-size:32px;line-height:1.22;font-weight:800;">' . frontend_tenis_qcup_e($title) . '</h1>
        </td></tr>
        <tr><td style="padding:34px 38px 38px;font-size:16px;line-height:1.68;color:#26323f;">' . $content . '</td></tr>
        <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:24px 38px;font-size:13px;line-height:1.55;color:#64748b;">
          &copy; ' . frontend_tenis_qcup_e(ui_text('tenis.email_sender_name')) . ' :: Astur &amp; Qanto s.r.o. ' . date('Y') . '
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_registration_rows(array $values, int $registrationId): array
{
    return [
        ui_text('tenis.email_registration_id') => (string)$registrationId,
        ui_text('tenis.email_year') => (string)$values['rok'],
        ui_text('tenis.email_date') => (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('d.m.Y H:i'),
        ui_text('tenis.team_name') => (string)$values['team_name'],
        sprintf(ui_text('tenis.email_player'), 1) => trim((string)$values['name1'] . ' ' . (string)$values['surname1']),
        sprintf(ui_text('tenis.email_player_email'), 1) => (string)$values['email1'],
        sprintf(ui_text('tenis.email_player_phone'), 1) => (string)$values['mobil1'],
        sprintf(ui_text('tenis.email_player'), 2) => trim((string)$values['name2'] . ' ' . (string)$values['surname2']),
        sprintf(ui_text('tenis.email_player_email'), 2) => (string)$values['email2'],
        sprintf(ui_text('tenis.email_player_phone'), 2) => (string)$values['mobil2'],
        ui_text('tenis.invited_by') => (string)$values['pozval'],
    ];
}

/** @param array<string, string> $rows */
function frontend_tenis_qcup_email_rows_html(array $rows): string
{
    $html = '<table role="presentation" cellpadding="7" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr style="border-bottom:1px solid #e5e7eb;"><th align="left" valign="top" style="width:42%;padding:9px 8px;color:#64748b;font-size:14px;">'
            . frontend_tenis_qcup_e($label)
            . '</th><td style="padding:9px 8px;color:#26323f;font-size:14px;">'
            . frontend_tenis_qcup_e($value !== '' ? $value : '—')
            . '</td></tr>';
    }

    return $html . '</table>';
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_notification_html(array $values, int $registrationId): string
{
    $content = frontend_tenis_qcup_email_rows_html(frontend_tenis_qcup_registration_rows($values, $registrationId));

    if ((string)$values['poznamka'] !== '') {
        $content .= '<h2 style="font-size:18px;margin:24px 0 8px;">' . frontend_tenis_qcup_e(ui_text('tenis.note')) . '</h2><div style="white-space:pre-wrap;padding:14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:10px;">'
            . frontend_tenis_qcup_e($values['poznamka']) . '</div>';
    }

    return frontend_tenis_qcup_email_layout(
        ui_text('tenis.email_internal_kicker'),
        ui_text('tenis.email_title'),
        $content
    );
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_confirmation_html(array $values, int $registrationId): string
{
    global $lang;

    $template = function_exists('stat_text')
        ? (string)(stat_text('tenisqcup_confirmation_email', (string)($lang ?? 'cz')) ?? '')
        : '';
    $template = strtr($template, [
        '{first_name}' => frontend_tenis_qcup_e($values['name1']),
        '{last_name}' => frontend_tenis_qcup_e($values['surname1']),
        '{team_name}' => frontend_tenis_qcup_e($values['team_name']),
        '{year}' => frontend_tenis_qcup_e($values['rok']),
        '{registration_id}' => frontend_tenis_qcup_e($registrationId),
    ]);
    $summaryRows = [
        ui_text('tenis.email_registration_id') => (string)$registrationId,
        ui_text('tenis.email_year') => (string)$values['rok'],
        ui_text('tenis.team_name') => (string)$values['team_name'],
    ];
    $content = $template;
    $content .= '<h2 style="font-size:20px;margin:28px 0 10px;">' . frontend_tenis_qcup_e(ui_text('tenis.confirmation_summary')) . '</h2>';
    $content .= frontend_tenis_qcup_email_rows_html($summaryRows);

    return frontend_tenis_qcup_email_layout(
        sprintf(ui_text('tenis.confirmation_kicker'), (int)$values['rok']),
        ui_text('tenis.confirmation_title'),
        $content
    );
}

function frontend_tenis_qcup_email_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/h[1-6]|/tr|/th|/td)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim(preg_replace("~\n{3,}~", "\n\n", $text) ?? $text);
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_second_player_error(array $values): ?string
{
    $secondPlayerValues = array_map(
        static fn(string $key): string => trim((string)($values[$key] ?? '')),
        ['name2', 'surname2', 'email2', 'mobil2']
    );
    if (array_filter($secondPlayerValues, static fn(string $value): bool => $value !== '') === []) {
        return null;
    }
    if ((string)($values['name2'] ?? '') === '' || (string)($values['surname2'] ?? '') === '') {
        return ui_text('tenis.error_player_2');
    }
    if ((string)($values['email2'] ?? '') !== ''
        && filter_var((string)$values['email2'], FILTER_VALIDATE_EMAIL) === false
    ) {
        return ui_text('tenis.error_email_2');
    }

    return null;
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_send_notification(PDO $pdo, array $values, int $registrationId): bool
{
    $recipient = frontend_tenis_qcup_setting_text('tenis_default-email-main', 'vodicka@qanto.cz');
    require_once ROOT_DIR . '/functions/fun_mailer.php';
    if (mailer_email_list($recipient) === []) {
        return false;
    }

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $config['smtp_from_name'] = ui_text('tenis.email_sender_name');
    $config['smtp_reply_to'] = (string)$values['email1'];
    $bodyHtml = frontend_tenis_qcup_notification_html($values, $registrationId);

    mailer_send_smtp_logged($pdo, $config, [
        'recipient_email' => $recipient,
        'recipient_name' => 'TenisQcup',
        'subject' => sprintf(ui_text('tenis.email_subject'), (int)$values['rok'], (string)$values['team_name']),
        'body_html' => $bodyHtml,
        'body_text' => frontend_tenis_qcup_email_text($bodyHtml),
    ], [
        'context' => 'rep_tenis_qcup',
        'template_code' => 'tenis_qcup_registration_internal',
        'related_table' => 'rep_tenis_qcup_registrace',
        'related_id' => $registrationId,
        'payload' => ['year' => (int)$values['rok']],
    ]);

    return true;
}

/** @param array<string, mixed> $values */
function frontend_tenis_qcup_send_confirmation(PDO $pdo, array $values, int $registrationId): bool
{
    $recipient = trim((string)$values['email1']);
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $config['smtp_from_name'] = ui_text('tenis.email_sender_name');
    $bodyHtml = frontend_tenis_qcup_confirmation_html($values, $registrationId);

    mailer_send_smtp_logged($pdo, $config, [
        'recipient_email' => $recipient,
        'recipient_name' => trim((string)$values['name1'] . ' ' . (string)$values['surname1']),
        'subject' => sprintf(ui_text('tenis.confirmation_subject'), (int)$values['rok']),
        'body_html' => $bodyHtml,
        'body_text' => frontend_tenis_qcup_email_text($bodyHtml),
    ], [
        'context' => 'rep_tenis_qcup',
        'template_code' => 'tenis_qcup_registration_confirmation',
        'related_table' => 'rep_tenis_qcup_registrace',
        'related_id' => $registrationId,
        'payload' => ['year' => (int)$values['rok']],
    ]);

    return true;
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string, values: array<string, mixed>, inserted_id?: int, mail_sent?: bool, confirmation_sent?: bool}
 */
function frontend_tenis_qcup_submit(array $post): array
{
    global $pdo;

    $values = [
        'rok' => frontend_tenis_qcup_year(),
        'team_name' => frontend_tenis_qcup_clean($post['team_name'] ?? ''),
        'name1' => frontend_tenis_qcup_clean($post['name1'] ?? '', 100),
        'surname1' => frontend_tenis_qcup_clean($post['surname1'] ?? '', 100),
        'email1' => mb_strtolower(frontend_tenis_qcup_clean($post['email1'] ?? '', 190), 'UTF-8'),
        'mobil1' => frontend_tenis_qcup_clean($post['mobil1'] ?? '', 50),
        'name2' => frontend_tenis_qcup_clean($post['name2'] ?? '', 100),
        'surname2' => frontend_tenis_qcup_clean($post['surname2'] ?? '', 100),
        'email2' => mb_strtolower(frontend_tenis_qcup_clean($post['email2'] ?? '', 190), 'UTF-8'),
        'mobil2' => frontend_tenis_qcup_clean($post['mobil2'] ?? '', 50),
        'pozval' => frontend_tenis_qcup_clean($post['pozval'] ?? '', 1000),
        'poznamka' => frontend_tenis_qcup_clean($post['poznamka'] ?? '', 3000),
        'souhlas' => isset($post['souhlas']) ? 1 : 0,
    ];

    try {
        if (!($pdo instanceof PDO)) {
            throw new RuntimeException(ui_text('tenis.error_database'));
        }
        if (!frontend_tenis_qcup_enabled()) {
            throw new InvalidArgumentException(ui_text('tenis.closed'));
        }

        if (function_exists('frontend_captcha_validate')) {
            $captcha = frontend_captcha_validate('tenis_qcup_registration', $post);
            if (!empty($captcha['bot'])) {
                return ['ok' => true, 'message' => ui_text('tenis.success'), 'values' => []];
            }
            if (empty($captcha['ok'])) {
                throw new InvalidArgumentException((string)$captcha['message']);
            }
        }

        $csrf = trim((string)($post['csrf_token'] ?? ''));
        $expectedCsrf = frontend_tenis_qcup_csrf_token();
        if ($csrf === '' || $expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
            throw new InvalidArgumentException(ui_text('tenis.error_security'));
        }

        foreach (['team_name', 'name1', 'surname1', 'email1', 'mobil1', 'pozval'] as $required) {
            if ((string)$values[$required] === '') {
                throw new InvalidArgumentException(ui_text('tenis.error_required'));
            }
        }
        if (filter_var($values['email1'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(ui_text('tenis.error_email_1'));
        }
        $secondPlayerError = frontend_tenis_qcup_second_player_error($values);
        if ($secondPlayerError !== null) {
            throw new InvalidArgumentException($secondPlayerError);
        }
        if ((int)$values['souhlas'] !== 1) {
            throw new InvalidArgumentException(ui_text('tenis.error_consent'));
        }

        $stmt = $pdo->prepare(
            'INSERT INTO rep_tenis_qcup_registrace
             (legacy_id, rok, datum, team_name, name1, surname1, email1, mobil1, name2, surname2, email2, mobil2, pozval, poznamka, valid, user_i, user_u)
             VALUES
             (NULL, :rok, CURDATE(), :team_name, :name1, :surname1, :email1, :mobil1, :name2, :surname2, :email2, :mobil2, :pozval, :poznamka, 1, "frontend_tenis_qcup", "frontend_tenis_qcup")'
        );
        $stmt->execute([
            ':rok' => $values['rok'],
            ':team_name' => $values['team_name'],
            ':name1' => $values['name1'],
            ':surname1' => $values['surname1'],
            ':email1' => $values['email1'],
            ':mobil1' => $values['mobil1'],
            ':name2' => $values['name2'],
            ':surname2' => $values['surname2'],
            ':email2' => $values['email2'],
            ':mobil2' => $values['mobil2'],
            ':pozval' => $values['pozval'],
            ':poznamka' => $values['poznamka'],
        ]);
        $insertedId = (int)$pdo->lastInsertId();
        $mailSent = false;
        try {
            $mailSent = frontend_tenis_qcup_send_notification($pdo, $values, $insertedId);
        } catch (Throwable) {
            $mailSent = false;
        }
        $confirmationSent = false;
        try {
            $confirmationSent = frontend_tenis_qcup_send_confirmation($pdo, $values, $insertedId);
        } catch (Throwable) {
            $confirmationSent = false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['frontend_tenis_qcup_csrf'] = bin2hex(random_bytes(24));
        }

        return [
            'ok' => true,
            'message' => ui_text('tenis.success'),
            'values' => [],
            'inserted_id' => $insertedId,
            'mail_sent' => $mailSent,
            'confirmation_sent' => $confirmationSent,
        ];
    } catch (InvalidArgumentException $e) {
        return ['ok' => false, 'message' => $e->getMessage(), 'values' => $values];
    } catch (Throwable) {
        return ['ok' => false, 'message' => ui_text('tenis.error_generic'), 'values' => $values];
    }
}
