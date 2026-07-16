<?php
declare(strict_types=1);

function frontend_contacts_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_contacts_text(string $key, string $fallback): string
{
    return function_exists('ui_text') ? ui_text($key, $fallback) : $fallback;
}

function frontend_contacts_expr(string $code, string $lang, string $fallback = ''): string
{
    $value = function_exists('stat_vyraz') ? stat_vyraz($code, $lang) : null;
    $value = trim((string)($value ?? ''));

    return $value !== '' ? $value : $fallback;
}

function frontend_contacts_localized(array $row, string $base, string $lang): string
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    $value = trim((string)($row[$base . '_' . $lang] ?? ''));
    if ($value !== '') {
        return $value;
    }

    return trim((string)($row[$base . '_cz'] ?? ''));
}

function frontend_contacts_plain(mixed $value): string
{
    $text = trim(strip_tags((string)$value));
    return trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
}

function frontend_contacts_safe_html(mixed $value): string
{
    $html = trim((string)$value);
    if ($html === '') {
        return '';
    }

    $html = preg_replace('~<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $html) ?? '';
    $html = strip_tags($html, '<strong><b><em><i><br><p><ul><ol><li>');
    $html = preg_replace('~<\s*(/?)\s*(strong|b|em|i|br|p|ul|ol|li)\b[^>]*>~i', '<$1$2>', $html) ?? '';

    return trim($html);
}

function frontend_contacts_file_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '' || str_contains($path, '..')) {
        return '';
    }

    $path = ltrim($path, '/');
    if (!is_file(ROOT_DIR . '/' . $path)) {
        return '';
    }

    return '/' . implode('/', array_map('rawurlencode', explode('/', $path)));
}

function frontend_contacts_anchor(string $value, int $id = 0): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $slug = strtolower(preg_replace('~[^a-z0-9]+~i', '-', $ascii !== false ? $ascii : $value) ?? '');
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'kontakt-' . $id;
}

function frontend_contacts_branch_short_name(string $name): string
{
    $name = trim($name);
    $name = preg_replace('~^Qanto\s+~iu', '', $name) ?? $name;

    return trim($name);
}

function frontend_contacts_form_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $token = $_SESSION['contacts_form_token'] ?? '';
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(24));
        $_SESSION['contacts_form_token'] = $token;
    }

    return $token;
}

function frontend_contacts_message(string $key, string $fallback): string
{
    return function_exists('ui_text') ? ui_text($key, $fallback) : $fallback;
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_contacts_form_categories(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $labelColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $stmt = $pdo->query(
        "SELECT id, {$labelColumn} AS label, nazev_cz
         FROM napiste_nam_kategorie
         WHERE valid = 1 AND visible = 1
         ORDER BY poradi ASC, nazev_cz ASC, id ASC"
    );

    $categories = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $label = trim((string)($row['label'] ?? ''));
        if ($label === '') {
            $label = trim((string)($row['nazev_cz'] ?? ''));
        }
        if ($label === '') {
            continue;
        }

        $categories[] = [
            'id' => (int)$row['id'],
            'label' => $label,
        ];
    }

    return $categories;
}

function frontend_contacts_form_category(int $categoryId): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $categoryId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, nazev_cz, email_to, email_copy
         FROM napiste_nam_kategorie
         WHERE id = :id AND valid = 1 AND visible = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $categoryId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function frontend_contacts_normalize_text(mixed $value, int $maxLength): string
{
    $value = trim((string)$value);
    $value = preg_replace('~[ \t]+~u', ' ', $value) ?? $value;
    $value = preg_replace("~\r\n?~", "\n", $value) ?? $value;

    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function frontend_contacts_email_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/h[1-6]|/li)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("~\n{3,}~", "\n\n", $text) ?? $text;

    return trim($text);
}

function frontend_contacts_send_message_email(PDO $pdo, array $category, array $values, int $messageId): void
{
    $recipient = trim((string)($category['email_to'] ?? ''));
    if ($recipient === '') {
        return;
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $config['smtp_reply_to'] = $values['email'];
    }

    $subject = 'Kontaktní formulář Qanto: ' . (string)($category['nazev_cz'] ?? '');
    $bodyHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#1d1d1b;">'
        . '<h1 style="font-size:20px;margin:0 0 16px;">Nová zpráva z kontaktního formuláře</h1>'
        . '<p><strong>Kategorie:</strong> ' . frontend_contacts_e((string)($category['nazev_cz'] ?? '')) . '</p>'
        . '<p><strong>Jméno:</strong> ' . frontend_contacts_e($values['name']) . '<br>'
        . '<strong>E-mail:</strong> ' . frontend_contacts_e($values['email']) . '<br>'
        . '<strong>Telefon:</strong> ' . frontend_contacts_e($values['phone']) . '</p>'
        . '<p><strong>Zpráva:</strong></p>'
        . '<div style="white-space:pre-wrap;padding:12px;border:1px solid #ddd;background:#f7f7f5;">'
        . frontend_contacts_e($values['message'])
        . '</div>'
        . '</div>';

    mailer_send_smtp_logged($pdo, $config, [
        'recipient_email' => $recipient,
        'recipient_name' => 'Qanto',
        'cc_emails' => (string)($category['email_copy'] ?? ''),
        'subject' => $subject,
        'body_html' => $bodyHtml,
        'body_text' => frontend_contacts_email_text($bodyHtml),
    ], [
        'context' => 'napiste_nam',
        'template_code' => 'contact_form_frontend',
        'related_table' => 'napiste_nam_zpravy',
        'related_id' => $messageId,
        'payload' => [
            'category_id' => (int)$category['id'],
            'sender_email' => $values['email'],
        ],
    ]);
}

/**
 * @param array<string, mixed> $data
 * @return array{ok: bool, message: string}
 */
function frontend_contacts_form_save(array $data): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_error', 'Zprávu se nepodařilo odeslat. Zkuste to prosím později.')];
    }

    if (function_exists('frontend_captcha_validate')) {
        $captcha = frontend_captcha_validate('contacts_form', $data);
        if (!empty($captcha['bot'])) {
            return ['ok' => true, 'message' => frontend_contacts_message('contacts.form_success', 'Zpráva byla přijata. Děkujeme.')];
        }
        if (empty($captcha['ok'])) {
            return ['ok' => false, 'message' => (string)$captcha['message']];
        }
    }

    $sessionToken = session_status() === PHP_SESSION_ACTIVE ? (string)($_SESSION['contacts_form_token'] ?? '') : '';
    $postedToken = (string)($data['csrf_token'] ?? '');
    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_invalid', 'Formulář vypršel. Odešlete ho prosím znovu.')];
    }

    $category = frontend_contacts_form_category((int)($data['category_id'] ?? 0));
    if ($category === null) {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_category_error', 'Vyberte prosím typ dotazu.')];
    }

    $values = [
        'name' => frontend_contacts_normalize_text($data['name'] ?? '', 512),
        'email' => trim(mb_strtolower((string)($data['email'] ?? ''), 'UTF-8')),
        'phone' => frontend_contacts_normalize_text($data['phone'] ?? '', 512),
        'message' => frontend_contacts_normalize_text($data['message'] ?? '', 5000),
    ];

    if ($values['name'] === '') {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_name_error', 'Zadejte prosím jméno a příjmení.')];
    }
    if ($values['email'] === '' || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_email_error', 'Zadejte prosím platný e-mail.')];
    }
    if ($values['message'] === '') {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_message_error', 'Napište prosím zprávu.')];
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO napiste_nam_zpravy
                (kategorie_id, datum, name, email, telefon, text, is_read, valid, user_i, user_u)
             VALUES
                (:kategorie_id, CURDATE(), :name, :email, :telefon, :text, 0, 1, "frontend_contact_form", "frontend_contact_form")'
        );
        $stmt->execute([
            ':kategorie_id' => (int)$category['id'],
            ':name' => $values['name'],
            ':email' => $values['email'],
            ':telefon' => $values['phone'],
            ':text' => $values['message'],
        ]);
        $messageId = (int)$pdo->lastInsertId();

        try {
            frontend_contacts_send_message_email($pdo, $category, $values, $messageId);
        } catch (Throwable) {
            // Zpráva zůstává v administraci; případné selhání SMTP je evidované v log_emails.
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['contacts_form_token'] = bin2hex(random_bytes(24));
        }

        return ['ok' => true, 'message' => frontend_contacts_message('contacts.form_success', 'Zpráva byla přijata. Děkujeme.')];
    } catch (Throwable) {
        return ['ok' => false, 'message' => frontend_contacts_message('contacts.form_error', 'Zprávu se nepodařilo odeslat. Zkuste to prosím později.')];
    }
}

/** @return array<int, array<string, mixed>> */
function frontend_contacts_wholesale_branches(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $nameColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $stmt = $pdo->query(
        "SELECT id, {$nameColumn} AS name, nazev_cz, mobil, email, adresa, vedouci, gps
         FROM pobocky
         WHERE typ = 'velkoobchod' AND valid = 1
         ORDER BY poradi ASC, nazev_cz ASC, id ASC"
    );

    $branches = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') {
            $name = trim((string)($row['nazev_cz'] ?? ''));
        }
        if ($name === '') {
            continue;
        }

        $branches[] = [
            'id' => (int)$row['id'],
            'name' => $name,
            'short_name' => frontend_contacts_branch_short_name($name),
            'anchor' => frontend_contacts_anchor($name, (int)$row['id']),
            'phone' => trim((string)($row['mobil'] ?? '')),
            'email' => trim((string)($row['email'] ?? '')),
            'address' => trim((string)($row['adresa'] ?? '')),
            'manager' => trim((string)($row['vedouci'] ?? '')),
            'gps' => trim((string)($row['gps'] ?? '')),
        ];
    }

    return $branches;
}

/** @return array<int, array<string, mixed>> */
function frontend_contacts_people_groups(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $groupNameColumn = $lang === 'en' ? 's.nazev_en' : 's.nazev_cz';
    $personRoleColumn = $lang === 'en' ? 'l.funkce_en' : 'l.funkce_cz';
    $personTextColumn = $lang === 'en' ? 'l.popis_en' : 'l.popis_cz';

    $stmt = $pdo->query(
        "SELECT l.id, l.jmeno, l.email, l.mobil, l.web, l.image,
                {$personRoleColumn} AS role_label,
                {$personTextColumn} AS description_label,
                l.funkce_cz, l.popis_cz,
                s.id AS group_id,
                {$groupNameColumn} AS group_label,
                s.nazev_cz AS group_label_cz
         FROM kontakty_lide l
         JOIN kontakty_lide_skupiny s ON s.id = l.skupina_id
         WHERE l.valid = 1 AND l.visible = 1
           AND s.valid = 1 AND s.visible = 1
         ORDER BY s.poradi ASC, s.nazev_cz ASC, l.poradi ASC, l.jmeno ASC, l.id ASC"
    );

    $groups = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $groupId = (int)$row['group_id'];
        $groupLabel = trim((string)($row['group_label'] ?? ''));
        if ($groupLabel === '') {
            $groupLabel = trim((string)($row['group_label_cz'] ?? ''));
        }
        if ($groupLabel === '') {
            continue;
        }

        if (!isset($groups[$groupId])) {
            $groups[$groupId] = [
                'id' => $groupId,
                'label' => $groupLabel,
                'people' => [],
            ];
        }

        $role = trim((string)($row['role_label'] ?? ''));
        if ($role === '') {
            $role = trim((string)($row['funkce_cz'] ?? ''));
        }
        $description = frontend_contacts_safe_html($row['description_label'] ?? '');
        if (frontend_contacts_plain($description) === '') {
            $description = frontend_contacts_safe_html($row['popis_cz'] ?? '');
        }

        $groups[$groupId]['people'][] = [
            'id' => (int)$row['id'],
            'name' => trim((string)($row['jmeno'] ?? '')),
            'email' => trim((string)($row['email'] ?? '')),
            'phone' => trim((string)($row['mobil'] ?? '')),
            'web' => trim((string)($row['web'] ?? '')),
            'image' => frontend_contacts_file_url($row['image'] ?? ''),
            'role' => $role,
            'description' => $description,
        ];
    }

    return array_values($groups);
}
