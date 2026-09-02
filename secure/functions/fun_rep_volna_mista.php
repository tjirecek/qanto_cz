<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function rep_volna_mista_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_volna_mista_bool_label(mixed $value): string
{
    return (int)$value === 1 ? 'ANO' : 'NE';
}

function rep_volna_mista_format_date(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($value))->format('d.m.Y');
    } catch (Throwable) {
        return $value;
    }
}

function rep_volna_mista_format_updated(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value;
}

/** @param array<string, mixed> $row */
function rep_volna_mista_updated_cell(array $row): string
{
    $date = rep_volna_mista_format_updated($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_volna_mista_e($user) . '</small>' : '';
    }

    return rep_volna_mista_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_volna_mista_e($user) . '</small>' : '');
}

function rep_volna_mista_application_pdf_url(int $id): string
{
    return '/secure/functions/ajax/rep_volna_mista_dotaznik_pdf.php?id=' . max(0, $id);
}

/** @param array<string, mixed> $application */
function rep_volna_mista_application_attachment_url(array $application, ?array $attachment = null): string
{
    if ($attachment !== null) {
        $path = trim((string)($attachment['file_path'] ?? ''));
        $attachmentId = (int)($attachment['id'] ?? 0);
        if ($attachmentId <= 0 || $path === '' || str_contains($path, '..')) {
            return '';
        }

        return '/secure/functions/ajax/rep_volna_mista_dotaznik_attachment.php?id=' . max(0, (int)($application['id'] ?? 0)) . '&attachment_id=' . $attachmentId;
    }

    $path = trim((string)($application['dot_priloha_file'] ?? ''));
    if ($path === '' || str_contains($path, '..')) {
        return '';
    }

    return '/secure/functions/ajax/rep_volna_mista_dotaznik_attachment.php?id=' . max(0, (int)($application['id'] ?? 0));
}

/** @param array<string, mixed> $application */
function rep_volna_mista_application_attachment_label(array $application): string
{
    $original = trim((string)($application['dot_priloha_original'] ?? ''));
    if ($original !== '') {
        return $original;
    }

    $path = trim((string)($application['dot_priloha_file'] ?? ''));
    return $path !== '' ? basename($path) : '';
}

/** @param array<string, mixed> $attachment */
function rep_volna_mista_application_attachment_row_label(array $attachment): string
{
    $original = trim((string)($attachment['original_name'] ?? ''));
    if ($original !== '') {
        return $original;
    }

    $path = trim((string)($attachment['file_path'] ?? ''));
    return $path !== '' ? basename($path) : '';
}

/** @return array<int, array<string, mixed>> */
function rep_volna_mista_application_attachments(PDO $pdo, int $applicationId): array
{
    if ($applicationId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT *
             FROM rep_volna_mista_dotaznik_prilohy
             WHERE dotaznik_id = :dotaznik_id AND valid = 1
             ORDER BY poradi ASC, id ASC'
        );
        $stmt->execute([':dotaznik_id' => $applicationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows !== []) {
            return $rows;
        }
    } catch (Throwable) {
        // Fallback for installations before the multi-attachment migration.
    }

    return [];
}

/** @return array<int, int> */
function rep_volna_mista_parse_ids(mixed $value): array
{
    $values = is_array($value) ? $value : [$value];
    $ids = [];
    foreach ($values as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

/** @return array<int, array<string, mixed>> */
function rep_volna_mista_types(PDO $pdo, ?int $valid = 1, string $order = 'default'): array
{
    $sql = 'SELECT t.*, COUNT(m.id) AS pozice_count
            FROM rep_volna_mista_typ t
            LEFT JOIN rep_volna_mista m ON m.typ_id = t.id AND m.valid = 1';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE t.valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' GROUP BY t.id ';
    $sql .= $order === 'name'
        ? 'ORDER BY t.nazev_cz ASC, t.stredisko_kod ASC, t.id ASC'
        : 'ORDER BY t.visible DESC, t.stredisko_kod ASC, t.nazev_cz ASC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volna_mista_type(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM rep_volna_mista_typ WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/** @return array<int, array<string, mixed>> */
function rep_volna_mista_contacts(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT l.id, l.jmeno, l.email, l.mobil, l.funkce_cz, s.nazev_cz AS skupina_nazev
         FROM kontakty_lide l
         LEFT JOIN kontakty_lide_skupiny s ON s.id = l.skupina_id
         WHERE l.valid = 1
         ORDER BY COALESCE(s.poradi, 999999) ASC, s.nazev_cz ASC, l.jmeno ASC, l.id ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** @param array<string, mixed> $contact */
function rep_volna_mista_contact_label(array $contact): string
{
    $name = trim((string)($contact['jmeno'] ?? ''));
    $group = trim((string)($contact['skupina_nazev'] ?? ''));
    $function = trim((string)($contact['funkce_cz'] ?? ''));
    $email = trim((string)($contact['email'] ?? ''));

    $parts = [$name];
    if ($function !== '') {
        $parts[] = $function;
    }
    if ($group !== '') {
        $parts[] = $group;
    }
    if ($email !== '') {
        $parts[] = $email;
    }

    return implode(' - ', array_filter($parts, static fn (string $value): bool => $value !== ''));
}

/** @param array<int, int> $typeIds @return array<int, array<string, mixed>> */
function rep_volna_mista_jobs(PDO $pdo, array $typeIds = [], int $valid = 1, ?int $visible = null): array
{
    $sql = 'SELECT m.*, t.nazev_cz AS typ_nazev_cz, t.stredisko_kod AS typ_stredisko_kod, t.email_up AS typ_email_up,
                   l.jmeno AS kontakt_jmeno, l.email AS kontakt_email, l.funkce_cz AS kontakt_funkce_cz
            FROM rep_volna_mista m
            LEFT JOIN rep_volna_mista_typ t ON t.id = m.typ_id
            LEFT JOIN kontakty_lide l ON l.id = m.kontakt_lide_id
            WHERE m.valid = :valid';
    $params = [':valid' => $valid === 1 ? 1 : 0];
    if ($visible !== null) {
        $sql .= ' AND m.visible = :visible';
        $params[':visible'] = $visible === 1 ? 1 : 0;
    }
    if ($typeIds !== []) {
        $placeholders = [];
        foreach (array_values($typeIds) as $index => $typeId) {
            $key = ':type' . $index;
            $placeholders[] = $key;
            $params[$key] = $typeId;
        }
        $sql .= ' AND m.typ_id IN (' . implode(', ', $placeholders) . ')';
    }
    $sql .= ' ORDER BY m.visible DESC, t.stredisko_kod ASC, t.nazev_cz ASC, m.id DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volna_mista_job(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM rep_volna_mista WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rep_volna_mista_job_with_type(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT m.*, t.nazev_cz AS typ_nazev_cz, t.popis_cz AS typ_popis_cz, t.email_up AS typ_email_up FROM rep_volna_mista m LEFT JOIN rep_volna_mista_typ t ON t.id = m.typ_id WHERE m.id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rep_volna_mista_setting_text(PDO $pdo, string $name, string $default = ''): string
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

function rep_volna_mista_up_template_code(string $type): string
{
    return match ($type) {
        'new' => 'rep_volna_mista_up_new',
        'cancel' => 'rep_volna_mista_up_cancel',
        default => throw new InvalidArgumentException('Neplatný typ oznámení na ÚP.'),
    };
}

/** @param array<string, mixed> $job */
function rep_volna_mista_up_placeholders(array $job): array
{
    $textValues = [
        '{job_id}' => (string)(int)($job['id'] ?? 0),
        '{job_name}' => (string)($job['nazev_cz'] ?? ''),
        '{type_name}' => (string)($job['typ_nazev_cz'] ?? ''),
        '{type_description}' => (string)($job['typ_popis_cz'] ?? ''),
        '{job_count}' => (string)(int)($job['pocet'] ?? 0),
    ];

    $escaped = [];
    foreach ($textValues as $key => $value) {
        $escaped[$key] = rep_volna_mista_e($value);
    }
    $escaped['{job_description}'] = (string)($job['popis_cz'] ?? '');

    return $escaped;
}

/** @param array<string, mixed> $job */
function rep_volna_mista_up_subject(PDO $pdo, string $type, array $job): string
{
    $setting = $type === 'cancel' ? 'rep_volna_mista_up_cancel_subject' : 'rep_volna_mista_up_new_subject';
    $default = $type === 'cancel' ? 'Zrušení pracovního místa na www.qanto.cz' : 'Nové pracovní místo na www.qanto.cz';
    $subject = rep_volna_mista_setting_text($pdo, $setting, $default);

    return trim(strtr($subject, rep_volna_mista_up_placeholders($job)));
}

/** @param array<string, mixed> $job */
function rep_volna_mista_up_body_html(PDO $pdo, string $type, array $job): string
{
    $setting = $type === 'cancel' ? 'rep_volna_mista_up_cancel_body' : 'rep_volna_mista_up_new_body';
    $default = $type === 'cancel'
        ? '<p><strong>Společnost <a href="https://www.qanto.cz">www.qanto.cz</a> zrušila na svých stránkách pracovní místo {job_name}</strong></p><p><strong>Název pracovní pozice: {job_name}</strong><br><strong>Pobočka: {type_name}</strong><br><strong>Popis pobočky: {type_description}</strong><br><strong>Počet volných míst: {job_count}</strong></p><p>{job_description}</p>'
        : '<p><strong>Společnost <a href="https://www.qanto.cz">www.qanto.cz</a> zadala na svých stránkách nové pracovní místo {job_name}</strong></p><p><strong>Název pracovní pozice: {job_name}</strong><br><strong>Pobočka: {type_name}</strong><br><strong>Adresa pobočky: {type_description}</strong><br><strong>Počet volných míst: {job_count}</strong></p><p>{job_description}</p>';
    $body = strtr(rep_volna_mista_setting_text($pdo, $setting, $default), rep_volna_mista_up_placeholders($job));

    return '<!doctype html><html lang="cs"><body style="font-family:Calibri,Verdana,Arial,sans-serif;font-size:14px;line-height:1.45;color:#111827;">' . $body . '</body></html>';
}

function rep_volna_mista_up_body_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/li)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function rep_volna_mista_up_config(PDO $pdo): array
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $fromEmail = rep_volna_mista_setting_text($pdo, 'rep_volna_mista_up_from_email', rep_volna_mista_setting_text($pdo, 'kariera_email_send'));
    $fromName = rep_volna_mista_setting_text($pdo, 'rep_volna_mista_up_from_name', 'Qanto - personální oddělení');

    if ($fromEmail !== '') {
        if (function_exists('mailer_is_local_environment') && mailer_is_local_environment()) {
            $config['smtp_reply_to'] = $fromEmail;
        } else {
            $config['smtp_from'] = $fromEmail;
        }
    }
    if ($fromName !== '') {
        $config['smtp_from_name'] = $fromName;
    }

    return $config;
}

function rep_volna_mista_send_up_notice(PDO $pdo, int $jobId, string $type): array
{
    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $type = $type === 'cancel' ? 'cancel' : 'new';
    $job = rep_volna_mista_job_with_type($pdo, $jobId);
    if (!$job) {
        throw new RuntimeException('Pracovní místo nebylo nalezeno.');
    }

    $recipient = trim((string)($job['typ_email_up'] ?? ''));
    if ($recipient === '') {
        throw new RuntimeException('U skupiny pracovního místa není vyplněný e-mail ÚP.');
    }

    $bodyHtml = rep_volna_mista_up_body_html($pdo, $type, $job);
    $subject = rep_volna_mista_up_subject($pdo, $type, $job);
    $templateCode = rep_volna_mista_up_template_code($type);
    $result = mailer_send_smtp_logged($pdo, rep_volna_mista_up_config($pdo), [
        'recipient_email' => $recipient,
        'recipient_name' => 'Úřad práce',
        'subject' => $subject,
        'body_html' => $bodyHtml,
        'body_text' => rep_volna_mista_up_body_text($bodyHtml),
    ], [
        'context' => 'rep_volna_mista',
        'template_code' => $templateCode,
        'related_table' => 'rep_volna_mista',
        'related_id' => $jobId,
        'payload' => [
            'notice_type' => $type,
            'job_id' => $jobId,
            'type_id' => (int)($job['typ_id'] ?? 0),
        ],
    ]);

    return $result + [
        'recipient' => $recipient,
        'notice_type' => $type,
    ];
}

function rep_volna_mista_save_type(PDO $pdo, array $data): int
{
    $id = (int)($data['id'] ?? 0);
    $params = [
        ':stredisko_kod' => max(0, (int)($data['stredisko_kod'] ?? 0)),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':popis_cz' => trim((string)($data['popis_cz'] ?? '')),
        ':popis_en' => trim((string)($data['popis_en'] ?? '')),
        ':email_up' => trim((string)($data['email_up'] ?? '')),
        ':visible' => !empty($data['visible']) ? 1 : 0,
        ':valid' => !empty($data['valid']) ? 1 : 0,
        ':user_u' => admin_session_user(),
    ];
    if ($params[':nazev_cz'] === '') {
        throw new RuntimeException('Název skupiny CZ je povinný.');
    }

    if ($id > 0) {
        $params[':id'] = $id;
        $stmt = $pdo->prepare('UPDATE rep_volna_mista_typ SET stredisko_kod = :stredisko_kod, nazev_cz = :nazev_cz, nazev_en = :nazev_en, popis_cz = :popis_cz, popis_en = :popis_en, email_up = :email_up, visible = :visible, valid = :valid, user_u = :user_u WHERE id = :id');
        $stmt->execute($params);
        admin_auto_translate_record('rep_volna_mista.type', $id, [
            'nazev_cz' => $params[':nazev_cz'],
            'nazev_en' => $params[':nazev_en'],
            'popis_cz' => $params[':popis_cz'],
            'popis_en' => $params[':popis_en'],
        ] + $data);
        return $id;
    }

    $params[':user_i'] = admin_session_user();
    $stmt = $pdo->prepare('INSERT INTO rep_volna_mista_typ (stredisko_kod, nazev_cz, nazev_en, popis_cz, popis_en, email_up, visible, valid, user_i, user_u) VALUES (:stredisko_kod, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :email_up, :visible, :valid, :user_i, :user_u)');
    $stmt->execute($params);
    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('rep_volna_mista.type', $newId, [
        'nazev_cz' => $params[':nazev_cz'],
        'nazev_en' => $params[':nazev_en'],
        'popis_cz' => $params[':popis_cz'],
        'popis_en' => $params[':popis_en'],
    ] + $data);

    return $newId;
}

function rep_volna_mista_save_job(PDO $pdo, array $data): int
{
    $id = (int)($data['id'] ?? 0);
    $typId = (int)($data['typ_id'] ?? 0);
    $kontaktLideId = (int)($data['kontakt_lide_id'] ?? 0);
    $params = [
        ':typ_id' => $typId > 0 ? $typId : null,
        ':pocet' => max(0, (int)($data['pocet'] ?? 0)),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':popis_cz' => editor_html(str_replace("\r\n", '', (string)($data['popis_cz'] ?? ''))),
        ':popis_en' => editor_html(str_replace("\r\n", '', (string)($data['popis_en'] ?? ''))),
        ':legacy_contact_id' => (int)($data['legacy_contact_id'] ?? 0) ?: null,
        ':kontakt_lide_id' => $kontaktLideId > 0 ? $kontaktLideId : null,
        ':visible' => !empty($data['visible']) ? 1 : 0,
        ':valid' => !empty($data['valid']) ? 1 : 0,
        ':user_u' => admin_session_user(),
    ];
    if ($params[':nazev_cz'] === '') {
        throw new RuntimeException('Název pozice CZ je povinný.');
    }
    if ($params[':typ_id'] === null) {
        throw new RuntimeException('Skupina pracovního místa je povinná.');
    }
    if ($params[':kontakt_lide_id'] !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kontakty_lide WHERE id = :id AND valid = 1');
        $stmt->execute([':id' => $params[':kontakt_lide_id']]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('Vybraný kontakt neexistuje nebo není validní.');
        }
    }

    if ($id > 0) {
        $params[':id'] = $id;
        $stmt = $pdo->prepare('UPDATE rep_volna_mista SET typ_id = :typ_id, pocet = :pocet, nazev_cz = :nazev_cz, nazev_en = :nazev_en, popis_cz = :popis_cz, popis_en = :popis_en, legacy_contact_id = :legacy_contact_id, kontakt_lide_id = :kontakt_lide_id, visible = :visible, valid = :valid, user_u = :user_u WHERE id = :id');
        $stmt->execute($params);
        admin_auto_translate_record('rep_volna_mista.job', $id, [
            'nazev_cz' => $params[':nazev_cz'],
            'nazev_en' => $params[':nazev_en'],
            'popis_cz' => $params[':popis_cz'],
            'popis_en' => $params[':popis_en'],
        ] + $data);
        return $id;
    }

    $params[':user_i'] = admin_session_user();
    $stmt = $pdo->prepare('INSERT INTO rep_volna_mista (typ_id, pocet, nazev_cz, nazev_en, popis_cz, popis_en, legacy_contact_id, kontakt_lide_id, visible, valid, user_i, user_u) VALUES (:typ_id, :pocet, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :legacy_contact_id, :kontakt_lide_id, :visible, :valid, :user_i, :user_u)');
    $stmt->execute($params);
    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('rep_volna_mista.job', $newId, [
        'nazev_cz' => $params[':nazev_cz'],
        'nazev_en' => $params[':nazev_en'],
        'popis_cz' => $params[':popis_cz'],
        'popis_en' => $params[':popis_en'],
    ] + $data);

    return $newId;
}

function rep_volna_mista_set_valid(PDO $pdo, string $table, int $id, int $valid): void
{
    $allowed = ['rep_volna_mista_typ', 'rep_volna_mista', 'rep_volna_mista_dotaznik'];
    if (!in_array($table, $allowed, true) || $id <= 0) {
        throw new RuntimeException('Neplatný požadavek.');
    }
    $stmt = $pdo->prepare('UPDATE `' . $table . '` SET valid = :valid, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':valid' => $valid === 1 ? 1 : 0,
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

function rep_volna_mista_count(PDO $pdo, string $table, int $valid): int
{
    $allowed = ['rep_volna_mista_typ', 'rep_volna_mista', 'rep_volna_mista_dotaznik'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

/** @return array<int, int> */
function rep_volna_mista_application_type_counts(PDO $pdo, int $valid): array
{
    $stmt = $pdo->prepare('SELECT typ_id, COUNT(*) AS total FROM rep_volna_mista_dotaznik WHERE valid = :valid GROUP BY typ_id');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);

    $counts = [];
    foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $counts[(int)$row['typ_id']] = (int)$row['total'];
    }
    return $counts;
}

/** @param array<int, int> $typeIds @return array<int, array<string, mixed>> */
function rep_volna_mista_applications(PDO $pdo, array $typeIds = [], int $valid = 1): array
{
    $sql = 'SELECT d.*, t.nazev_cz AS typ_nazev_cz, m.nazev_cz AS misto_nazev_cz,
                   COALESCE(p.attachment_count, 0) AS attachment_count
            FROM rep_volna_mista_dotaznik d
            LEFT JOIN rep_volna_mista_typ t ON t.id = d.typ_id
            LEFT JOIN rep_volna_mista m ON m.id = d.volne_misto_id
            LEFT JOIN (
                SELECT dotaznik_id, COUNT(*) AS attachment_count
                FROM rep_volna_mista_dotaznik_prilohy
                WHERE valid = 1
                GROUP BY dotaznik_id
            ) p ON p.dotaznik_id = d.id
            WHERE d.valid = :valid';
    $params = [':valid' => $valid === 1 ? 1 : 0];
    if ($typeIds !== []) {
        $placeholders = [];
        foreach (array_values($typeIds) as $index => $typeId) {
            $key = ':type' . $index;
            $placeholders[] = $key;
            $params[$key] = $typeId;
        }
        $sql .= ' AND d.typ_id IN (' . implode(', ', $placeholders) . ')';
    }
    $sql .= ' ORDER BY d.dot_datum DESC, d.id DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volna_mista_application(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT d.*, t.nazev_cz AS typ_nazev_cz, m.nazev_cz AS misto_nazev_cz FROM rep_volna_mista_dotaznik d LEFT JOIN rep_volna_mista_typ t ON t.id = d.typ_id LEFT JOIN rep_volna_mista m ON m.id = d.volne_misto_id WHERE d.id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
