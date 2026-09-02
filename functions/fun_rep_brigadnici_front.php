<?php
declare(strict_types=1);

function frontend_brigadnici_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_brigadnici_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (!isset($_SESSION['frontend_brigadnici_csrf'])) {
        $_SESSION['frontend_brigadnici_csrf'] = bin2hex(random_bytes(16));
    }

    return (string)$_SESSION['frontend_brigadnici_csrf'];
}

function frontend_brigadnici_clean_value(mixed $value, int $limit = 1000): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace("~\r\n?~", "\n", $value) ?? $value;
    $value = preg_replace('~[^\P{C}\n\t]+~u', '', $value) ?? $value;
    $value = trim($value);

    if (mb_strlen($value, 'UTF-8') > $limit) {
        $value = mb_substr($value, 0, $limit, 'UTF-8');
    }

    return $value;
}

function frontend_brigadnici_form_value(array $values, string $key): string
{
    return frontend_brigadnici_e($values[$key] ?? '');
}

function frontend_brigadnici_branch_record_type(string $branchType): string
{
    return $branchType === 'market' ? 'mo' : 'vo';
}

function frontend_brigadnici_branch_group_label(string $branchType): string
{
    return match ($branchType) {
        'market' => ui_text('brigada.branch_type_markets'),
        'prodejna' => ui_text('brigada.branch_type_qantoplus'),
        default => ui_text('brigada.branch_type_wholesale'),
    };
}

function frontend_brigadnici_branch_type_label(string $branchType): string
{
    return match ($branchType) {
        'market' => ui_text('brigada.branch_market'),
        'prodejna' => ui_text('brigada.branch_qantoplus'),
        default => ui_text('brigada.branch_wholesale'),
    };
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_brigadnici_branches(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $nameColumn = $lang === 'en' ? 'COALESCE(NULLIF(nazev_en, ""), nazev_cz)' : 'nazev_cz';
    $stmt = $pdo->query(
        "SELECT id, typ, {$nameColumn} AS nazev, nazev_cz, adresa, stredisko
         FROM pobocky
         WHERE valid = 1
         ORDER BY FIELD(typ, 'market', 'prodejna', 'velkoobchod'), nazev_cz"
    );

    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $branches = [];
    foreach ($rows as $row) {
        $type = (string)($row['typ'] ?? '');
        $title = trim((string)($row['nazev'] ?? ''));
        $address = trim((string)($row['adresa'] ?? ''));
        $branches[] = [
            'id' => (int)($row['id'] ?? 0),
            'type' => $type,
            'record_type' => frontend_brigadnici_branch_record_type($type),
            'type_label' => frontend_brigadnici_branch_type_label($type),
            'group_label' => frontend_brigadnici_branch_group_label($type),
            'title' => $title,
            'address' => $address,
            'stredisko' => trim((string)($row['stredisko'] ?? '')),
            'search' => trim($title . ' ' . $address . ' ' . (string)($row['stredisko'] ?? '')),
        ];
    }

    return $branches;
}

/**
 * @return array<string, mixed>|null
 */
function frontend_brigadnici_branch(PDO $pdo, int $id, string $lang = 'cz'): ?array
{
    if ($id <= 0) {
        return null;
    }

    $nameColumn = $lang === 'en' ? 'COALESCE(NULLIF(nazev_en, ""), nazev_cz)' : 'nazev_cz';
    $stmt = $pdo->prepare(
        "SELECT id, typ, {$nameColumn} AS nazev, adresa, stredisko, email_brigada, email_kariera
         FROM pobocky
         WHERE id = :id AND valid = 1
         LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function frontend_brigadnici_email_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/h[1-6]|/li)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("~\n{3,}~", "\n\n", $text) ?? $text;

    return trim($text);
}

function frontend_brigadnici_setting_text(PDO $pdo, string $name, string $default = ''): string
{
    if (function_exists('sp_hodnota_text')) {
        $value = sp_hodnota_text($name);
        return trim((string)$value) !== '' ? (string)$value : $default;
    }

    $stmt = $pdo->prepare('SELECT hodnota_text FROM settings WHERE name = :name AND valid = 1 LIMIT 1');
    $stmt->execute([':name' => $name]);
    $value = $stmt->fetchColumn();

    return $value !== false && trim((string)$value) !== '' ? (string)$value : $default;
}

function frontend_brigadnici_template_values(array $branch, array $values, int $registrationId, string $recordType, string $regDate): array
{
    $firstName = (string)($values['jmeno'] ?? '');
    $lastName = (string)($values['prijmeni'] ?? '');

    return [
        '{first_name}' => $firstName,
        '{last_name}' => $lastName,
        '{full_name}' => trim($firstName . ' ' . $lastName),
        '{email}' => (string)($values['email'] ?? ''),
        '{phone}' => (string)($values['mobil'] ?? ''),
        '{branch_name}' => (string)($branch['nazev'] ?? ''),
        '{branch_address}' => (string)($branch['adresa'] ?? ''),
        '{registration_id}' => (string)$registrationId,
        '{registration_type}' => strtoupper($recordType),
        '{registration_date}' => $regDate,
    ];
}

function frontend_brigadnici_apply_template(string $template, array $placeholders, bool $escapeHtml = true): string
{
    $replace = [];
    foreach ($placeholders as $key => $value) {
        $replace[$key] = $escapeHtml ? frontend_brigadnici_e($value) : (string)$value;
    }

    return strtr($template, $replace);
}

function frontend_brigadnici_registration_email_html(array $branch, array $values, int $registrationId, string $recordType, string $regDate): string
{
    $typeLabel = strtoupper($recordType);
    $branchName = trim((string)($branch['nazev'] ?? ''));
    $branchAddress = trim((string)($branch['adresa'] ?? ''));
    $experience = ((int)($values['zkusenosti_l'] ?? 0) === 1) ? 'Ano' : 'Ne';
    $note = trim((string)($values['poznamka'] ?? ''));

    $body = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#1d1d1b;">'
        . '<h1 style="font-size:20px;margin:0 0 16px;">Nová registrace brigádníka</h1>'
        . '<p><strong>Typ:</strong> ' . frontend_brigadnici_e($typeLabel) . '<br>'
        . '<strong>ID registrace:</strong> ' . $registrationId . '<br>'
        . '<strong>Datum registrace:</strong> ' . frontend_brigadnici_e($regDate) . '</p>'
        . '<p><strong>Jméno a příjmení:</strong> ' . frontend_brigadnici_e($values['jmeno']) . ' ' . frontend_brigadnici_e($values['prijmeni']) . '<br>'
        . '<strong>Telefon:</strong> ' . frontend_brigadnici_e($values['mobil']) . '<br>'
        . '<strong>E-mail:</strong> ' . frontend_brigadnici_e($values['email']) . '</p>'
        . '<p><strong>Pobočka:</strong> ' . frontend_brigadnici_e($branchName);

    if ($branchAddress !== '') {
        $body .= '<br><strong>Adresa:</strong> ' . frontend_brigadnici_e($branchAddress);
    }

    $body .= '<br><strong>Zkušenosti s prací v maloobchodě či velkoobchodě:</strong> ' . $experience . '</p>';

    if ($note !== '') {
        $body .= '<p><strong>Poznámka:</strong></p>'
            . '<div style="white-space:pre-wrap;padding:12px;border:1px solid #ddd;background:#f7f7f5;">'
            . frontend_brigadnici_e($note)
            . '</div>';
    }

    return $body . '</div>';
}

function frontend_brigadnici_confirmation_subject(PDO $pdo, array $branch, array $values, int $registrationId, string $recordType, string $regDate): string
{
    $template = frontend_brigadnici_setting_text(
        $pdo,
        'rep_brigadnici_confirmation_subject',
        'Potvrzení registrace brigádníka Qanto'
    );
    $subject = frontend_brigadnici_apply_template(
        $template,
        frontend_brigadnici_template_values($branch, $values, $registrationId, $recordType, $regDate),
        false
    );
    $subject = html_entity_decode(strip_tags($subject), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $subject = preg_replace('~\s+~u', ' ', $subject) ?? $subject;

    return trim($subject) !== '' ? trim($subject) : 'Potvrzení registrace brigádníka Qanto';
}

function frontend_brigadnici_confirmation_email_html(PDO $pdo, array $branch, array $values, int $registrationId, string $recordType, string $regDate): string
{
    $default = '<p>Dobrý den,</p><p>děkujeme za registraci brigádníka u společnosti Qanto.</p><p>Vaši registraci pro pobočku <strong>{branch_name}</strong> jsme přijali a budeme vás kontaktovat na uvedených kontaktních údajích.</p><p>S pozdravem<br>Qanto</p>';
    $template = frontend_brigadnici_setting_text($pdo, 'rep_brigadnici_confirmation_body', $default);
    $body = frontend_brigadnici_apply_template(
        $template,
        frontend_brigadnici_template_values($branch, $values, $registrationId, $recordType, $regDate),
        true
    );

    return '<!doctype html><html lang="cs"><body style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#1d1d1b;">'
        . $body
        . '</body></html>';
}

function frontend_brigadnici_send_internal_email(PDO $pdo, array $branch, array $values, int $registrationId, string $recordType, string $regDate): bool
{
    $recipient = trim((string)($branch['email_brigada'] ?? ''));
    if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $config['smtp_reply_to'] = $values['email'];
    }

    $subject = strtoupper($recordType) . ': Registrace brigádníka - '
        . (string)$values['jmeno'] . ' ' . (string)$values['prijmeni'];
    $bodyHtml = frontend_brigadnici_registration_email_html($branch, $values, $registrationId, $recordType, $regDate);

    mailer_send_smtp_logged($pdo, $config, [
        'recipient_email' => $recipient,
        'recipient_name' => trim((string)($branch['nazev'] ?? 'Qanto')),
        'subject' => $subject,
        'body_html' => $bodyHtml,
        'body_text' => frontend_brigadnici_email_text($bodyHtml),
    ], [
        'context' => 'rep_brigadnici',
        'template_code' => 'brigada_registration_internal',
        'related_table' => 'rep_brigadnici_registrace',
        'related_id' => $registrationId,
        'payload' => [
            'branch_id' => (int)($branch['id'] ?? 0),
            'branch_type' => (string)($branch['typ'] ?? ''),
            'record_type' => $recordType,
        ],
    ]);

    return true;
}

function frontend_brigadnici_send_confirmation_email(PDO $pdo, array $branch, array $values, int $registrationId, string $recordType, string $regDate): bool
{
    $recipient = trim((string)($values['email'] ?? ''));
    if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $bodyHtml = frontend_brigadnici_confirmation_email_html($pdo, $branch, $values, $registrationId, $recordType, $regDate);

    mailer_send_smtp_logged($pdo, $config, [
        'recipient_email' => $recipient,
        'recipient_name' => trim((string)$values['jmeno'] . ' ' . (string)$values['prijmeni']),
        'subject' => frontend_brigadnici_confirmation_subject($pdo, $branch, $values, $registrationId, $recordType, $regDate),
        'body_html' => $bodyHtml,
        'body_text' => frontend_brigadnici_email_text($bodyHtml),
    ], [
        'context' => 'rep_brigadnici',
        'template_code' => 'brigada_registration_confirmation',
        'related_table' => 'rep_brigadnici_registrace',
        'related_id' => $registrationId,
        'payload' => [
            'branch_id' => (int)($branch['id'] ?? 0),
            'branch_type' => (string)($branch['typ'] ?? ''),
            'record_type' => $recordType,
        ],
    ]);

    return true;
}

/**
 * @return array{ok: bool, message: string, values: array<string, mixed>, inserted_id?: int, mail_sent?: bool, confirmation_sent?: bool}
 */
function frontend_brigadnici_submit(array $post, string $lang = 'cz'): array
{
    global $pdo;

    $values = [
        'pobocka_id' => max(0, (int)($post['pobocka_id'] ?? 0)),
        'jmeno' => frontend_brigadnici_clean_value($post['jmeno'] ?? '', 120),
        'prijmeni' => frontend_brigadnici_clean_value($post['prijmeni'] ?? '', 120),
        'mobil' => frontend_brigadnici_clean_value($post['mobil'] ?? '', 50),
        'email' => trim(mb_strtolower(frontend_brigadnici_clean_value($post['email'] ?? '', 190), 'UTF-8')),
        'zkusenosti_l' => isset($post['zkusenosti_l']) ? '1' : '0',
        'poznamka' => frontend_brigadnici_clean_value($post['poznamka'] ?? '', 2000),
    ];

    try {
        if (!($pdo instanceof PDO)) {
            throw new RuntimeException(ui_text('brigada.error_generic'));
        }

        if (function_exists('frontend_captcha_validate')) {
            $captcha = frontend_captcha_validate('brigada_registration', $post);
            if (!empty($captcha['bot'])) {
                return [
                    'ok' => true,
                    'message' => ui_text('brigada.success'),
                    'values' => [],
                ];
            }
            if (empty($captcha['ok'])) {
                throw new RuntimeException((string)$captcha['message']);
            }
        }

        $csrf = (string)($post['csrf_token'] ?? '');
        $expectedCsrf = frontend_brigadnici_csrf_token();
        if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
            throw new RuntimeException(ui_text('brigada.error_security'));
        }

        $branch = frontend_brigadnici_branch($pdo, (int)$values['pobocka_id'], $lang);
        if ($branch === null) {
            throw new RuntimeException(ui_text('brigada.error_branch'));
        }

        if ($values['jmeno'] === '' || $values['prijmeni'] === '' || $values['mobil'] === '' || $values['email'] === '') {
            throw new RuntimeException(ui_text('brigada.error_required'));
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(ui_text('brigada.error_email'));
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
        $recordType = frontend_brigadnici_branch_record_type((string)$branch['typ']);
        $stmt = $pdo->prepare(
            'INSERT INTO rep_brigadnici_registrace
             (typ, legacy_id, rok, aktivni, jmeno, prijmeni, mobil, email, poznamka, zkusenosti_l, legacy_password, reg_date, pobocka_id, pobocka_ref_id, valid, user_i, user_u)
             VALUES
             (:typ, NULL, :rok, 1, :jmeno, :prijmeni, :mobil, :email, :poznamka, :zkusenosti_l, "", :reg_date, :pobocka_id, :pobocka_ref_id, 1, "frontend_brigada", "frontend_brigada")'
        );
        $stmt->execute([
            ':typ' => $recordType,
            ':rok' => (int)$now->format('Y'),
            ':jmeno' => $values['jmeno'],
            ':prijmeni' => $values['prijmeni'],
            ':mobil' => $values['mobil'],
            ':email' => $values['email'],
            ':poznamka' => $values['poznamka'],
            ':zkusenosti_l' => (int)$values['zkusenosti_l'],
            ':reg_date' => $now->format('Y-m-d H:i:s'),
            ':pobocka_id' => (int)$branch['id'],
            ':pobocka_ref_id' => (int)$branch['id'],
        ]);
        $insertedId = (int)$pdo->lastInsertId();
        $mailSent = false;
        $confirmationSent = false;
        try {
            $mailSent = frontend_brigadnici_send_internal_email(
                $pdo,
                $branch,
                $values,
                $insertedId,
                $recordType,
                $now->format('d.m.Y H:i')
            );
        } catch (Throwable) {
            $mailSent = false;
        }
        try {
            $confirmationSent = frontend_brigadnici_send_confirmation_email(
                $pdo,
                $branch,
                $values,
                $insertedId,
                $recordType,
                $now->format('d.m.Y H:i')
            );
        } catch (Throwable) {
            $confirmationSent = false;
        }

        return [
            'ok' => true,
            'message' => ui_text('brigada.success'),
            'values' => [],
            'inserted_id' => $insertedId,
            'mail_sent' => $mailSent,
            'confirmation_sent' => $confirmationSent,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => $e->getMessage(),
            'values' => $values,
        ];
    }
}
