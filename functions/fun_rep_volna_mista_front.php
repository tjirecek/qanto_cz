<?php
declare(strict_types=1);

function frontend_volna_mista_text(string $code, string $lang, string $fallback): string
{
    $value = function_exists('stat_vyraz') ? stat_vyraz($code, $lang) : null;
    $value = trim((string)($value ?? ''));
    return $value !== '' ? $value : $fallback;
}

function frontend_volna_mista_localized(array $row, string $base, string $lang): string
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    $value = trim((string)($row[$base . '_' . $lang] ?? ''));
    if ($value !== '') {
        return $value;
    }
    return trim((string)($row[$base . '_cz'] ?? ''));
}

function frontend_volna_mista_plain_text(string $html, int $limit = 180): string
{
    $text = trim(preg_replace('~\s+~u', ' ', strip_tags($html)) ?? '');
    if ($text === '' || mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
}

function frontend_volna_mista_form_value(array $values, string $key): string
{
    return htmlspecialchars((string)($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function frontend_volna_mista_branch_city(string $address, string $fallbackTitle = ''): string
{
    $address = trim($address);
    if ($address !== '') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn(string $part): bool => $part !== ''));
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $candidate = preg_replace('/\b\d{3}\s?\d{2}\b/u', '', $parts[$i]);
            $candidate = preg_replace('/\s+\d+[A-Za-z]?([\/-]\d+)?$/u', '', (string)$candidate);
            $candidate = trim((string)$candidate, " \t\n\r\0\x0B,");
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    $fallbackTitle = preg_replace('/^(Qanto\+?|Market|Supermarket)\s+/u', '', trim($fallbackTitle));
    $fallbackTitle = preg_replace('/\s*[-,].+$/u', '', (string)$fallbackTitle);

    return trim((string)$fallbackTitle);
}

function frontend_volna_mista_parse_gps(string $gps): ?array
{
    $gps = trim($gps);
    if ($gps === '') {
        return null;
    }

    $parts = preg_split('~\s*,\s*~', $gps);
    if (!is_array($parts) || count($parts) < 2) {
        return null;
    }

    $lat = frontend_volna_mista_parse_gps_part((string)$parts[0]);
    $lon = frontend_volna_mista_parse_gps_part((string)$parts[1]);
    if ($lat === null || $lon === null) {
        return null;
    }

    if ($lat < 40 || $lat > 60 || $lon < 8 || $lon > 25) {
        return null;
    }

    return ['lat' => $lat, 'lon' => $lon];
}

function frontend_volna_mista_parse_gps_part(string $value): ?float
{
    $value = strtoupper(trim($value));
    if ($value === '') {
        return null;
    }

    $sign = (str_contains($value, 'S') || str_contains($value, 'W')) ? -1.0 : 1.0;
    if (!preg_match('~-?\d+(?:[\.,]\d+)?~', $value, $match)) {
        return null;
    }

    return (float)str_replace(',', '.', $match[0]) * $sign;
}

function frontend_volna_mista_branch_cities_by_stredisko(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $stmt = $pdo->query(
        'SELECT stredisko, nazev_cz, nazev_en, adresa
         FROM pobocky
         WHERE valid = 1 AND stredisko IS NOT NULL AND stredisko <> \'\'
         ORDER BY FIELD(typ, \'velkoobchod\', \'prodejna\', \'market\'), poradi ASC, id ASC'
    );

    $cities = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $stredisko = (int)$row['stredisko'];
        if ($stredisko <= 0 || isset($cities[$stredisko])) {
            continue;
        }

        $title = frontend_volna_mista_localized($row, 'nazev', $lang);
        $city = frontend_volna_mista_branch_city((string)($row['adresa'] ?? ''), $title);
        if ($city !== '') {
            $cities[$stredisko] = $city;
        }
    }

    return $cities;
}

function frontend_volna_mista_map_branches(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $sql = 'SELECT p.id, p.typ, p.stredisko, p.nazev_cz, p.nazev_en, p.adresa, p.gps,
                   COALESCE(j.jobs_count, 0) AS jobs_count,
                   j.location_id
            FROM pobocky p
            LEFT JOIN (
                SELECT t.stredisko_kod, MIN(t.id) AS location_id, SUM(m.pocet) AS jobs_count
                FROM rep_volna_mista_typ t
                INNER JOIN rep_volna_mista m ON m.typ_id = t.id AND m.valid = 1 AND m.visible = 1
                WHERE t.valid = 1 AND t.visible = 1
                GROUP BY t.stredisko_kod
            ) j ON CAST(p.stredisko AS UNSIGNED) = j.stredisko_kod
            WHERE p.valid = 1 AND p.gps IS NOT NULL AND p.gps <> \'\'
            ORDER BY FIELD(p.typ, \'velkoobchod\', \'prodejna\', \'market\'), p.poradi ASC, p.nazev_cz ASC';

    $branches = [];
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $gps = frontend_volna_mista_parse_gps((string)($row['gps'] ?? ''));
        if ($gps === null) {
            continue;
        }

        $jobsCount = (int)($row['jobs_count'] ?? 0);
        $locationId = (int)($row['location_id'] ?? 0);
        $title = frontend_volna_mista_localized($row, 'nazev', $lang);
        $address = frontend_volna_mista_plain_text((string)($row['adresa'] ?? ''), 140);
        $branches[] = [
            'id' => (int)$row['id'],
            'type' => (string)($row['typ'] ?? ''),
            'stredisko' => (int)($row['stredisko'] ?? 0),
            'title' => $title,
            'city' => frontend_volna_mista_branch_city((string)($row['adresa'] ?? ''), $title),
            'address' => $address,
            'lat' => $gps['lat'],
            'lon' => $gps['lon'],
            'jobs_count' => $jobsCount,
            'location_id' => $locationId > 0 ? $locationId : null,
        ];
    }

    return $branches;
}

function frontend_volna_mista_jobs(string $lang = 'cz', ?int $locationId = null): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $params = [];
    $where = 'm.valid = 1 AND m.visible = 1 AND t.valid = 1 AND t.visible = 1';
    if ($locationId !== null && $locationId > 0) {
        $where .= ' AND t.id = :location_id';
        $params[':location_id'] = $locationId;
    }

    $stmt = $pdo->prepare(
        'SELECT m.id, m.pocet, m.nazev_cz, m.nazev_en, m.popis_cz, m.popis_en,
                t.id AS typ_id, t.nazev_cz AS typ_nazev_cz, t.nazev_en AS typ_nazev_en,
                t.popis_cz AS typ_popis_cz, t.popis_en AS typ_popis_en,
                t.stredisko_kod,
                l.jmeno AS kontakt_jmeno, l.email AS kontakt_email, l.mobil AS kontakt_mobil
         FROM rep_volna_mista m
         INNER JOIN rep_volna_mista_typ t ON t.id = m.typ_id
         LEFT JOIN kontakty_lide l ON l.id = m.kontakt_lide_id AND l.valid = 1
         WHERE ' . $where . '
         ORDER BY m.nazev_cz ASC, t.nazev_cz ASC, m.id DESC'
    );
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();

    $jobs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $id = (int)$row['id'];
        $jobs[] = [
            'id' => $id,
            'title' => frontend_volna_mista_localized($row, 'nazev', $lang),
            'description' => frontend_volna_mista_plain_text(frontend_volna_mista_localized($row, 'popis', $lang), 180),
            'location_id' => (int)$row['typ_id'],
            'stredisko_kod' => (int)$row['stredisko_kod'],
            'location' => frontend_volna_mista_localized([
                'nazev_cz' => $row['typ_nazev_cz'] ?? '',
                'nazev_en' => $row['typ_nazev_en'] ?? '',
            ], 'nazev', $lang),
            'address' => frontend_volna_mista_plain_text(frontend_volna_mista_localized([
                'popis_cz' => $row['typ_popis_cz'] ?? '',
                'popis_en' => $row['typ_popis_en'] ?? '',
            ], 'popis', $lang), 140),
            'count' => (int)$row['pocet'],
            'contact_name' => trim((string)($row['kontakt_jmeno'] ?? '')),
            'contact_email' => trim((string)($row['kontakt_email'] ?? '')),
            'url' => '/' . rawurlencode($lang) . '/kariera?pozice=' . $id,
        ];
    }

    return $jobs;
}

function frontend_volna_mista_job_detail(int $id, string $lang = 'cz'): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT m.id, m.pocet, m.nazev_cz, m.nazev_en, m.popis_cz, m.popis_en,
                t.id AS typ_id, t.nazev_cz AS typ_nazev_cz, t.nazev_en AS typ_nazev_en,
                t.popis_cz AS typ_popis_cz, t.popis_en AS typ_popis_en,
                l.jmeno AS kontakt_jmeno, l.email AS kontakt_email, l.mobil AS kontakt_mobil
         FROM rep_volna_mista m
         INNER JOIN rep_volna_mista_typ t ON t.id = m.typ_id
         LEFT JOIN kontakty_lide l ON l.id = m.kontakt_lide_id AND l.valid = 1
         WHERE m.id = :id
           AND m.valid = 1 AND m.visible = 1
           AND t.valid = 1 AND t.visible = 1
         LIMIT 1'
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int)$row['id'],
        'title' => frontend_volna_mista_localized($row, 'nazev', $lang),
        'content' => frontend_volna_mista_localized($row, 'popis', $lang),
        'location_id' => (int)$row['typ_id'],
        'location' => frontend_volna_mista_localized([
            'nazev_cz' => $row['typ_nazev_cz'] ?? '',
            'nazev_en' => $row['typ_nazev_en'] ?? '',
        ], 'nazev', $lang),
        'address' => frontend_volna_mista_plain_text(frontend_volna_mista_localized([
            'popis_cz' => $row['typ_popis_cz'] ?? '',
            'popis_en' => $row['typ_popis_en'] ?? '',
        ], 'popis', $lang), 140),
        'count' => (int)$row['pocet'],
        'contact_name' => trim((string)($row['kontakt_jmeno'] ?? '')),
        'contact_email' => trim((string)($row['kontakt_email'] ?? '')),
        'contact_phone' => trim((string)($row['kontakt_mobil'] ?? '')),
    ];
}

function frontend_volna_mista_private_storage_root(): string
{
    return ROOT_DIR . '/_files';
}

function frontend_volna_mista_application_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (!isset($_SESSION['frontend_volna_mista_application_csrf'])) {
        $_SESSION['frontend_volna_mista_application_csrf'] = bin2hex(random_bytes(16));
    }

    return (string)$_SESSION['frontend_volna_mista_application_csrf'];
}

function frontend_volna_mista_clean_post_value(mixed $value, int $limit = 4000): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace("~\r\n?~", "\n", $value) ?? $value;
    $value = strip_tags($value);

    return mb_substr(trim($value), 0, $limit, 'UTF-8');
}

function frontend_volna_mista_upload_slug(string $value, string $fallback = 'soubor'): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $slug = strtolower(preg_replace('~[^a-z0-9]+~i', '-', $ascii !== false ? $ascii : $value) ?? '');
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : $fallback;
}

function frontend_volna_mista_mail_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_volna_mista_application_email_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/li|/tr)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

/**
 * @return array<string, string>
 */
function frontend_volna_mista_application_pdf_fields(): array
{
    return [
        'dot_adresa' => 'Adresa',
        'dot_birthday' => 'Datum narození',
        'dot_vzdelani' => 'Vzdělání',
        'dot_rp' => 'ŘP',
        'dot_jazyk' => 'Jazyky',
        'dot_pc' => 'PC',
        'dot_predchozizam' => 'Předchozí zaměstnavatel',
        'dot_funkcezam' => 'Funkce',
        'dot_delkazam' => 'Délka zaměstnání',
        'dot_pracdoba' => 'Pracovní doba',
        'dot_plat' => 'Plat',
        'dot_koureni' => 'Kouření',
        'dot_rejstrik' => 'Rejstřík',
        'dot_zdravstav' => 'Zdravotní stav',
        'dot_zaliby' => 'Záliby',
        'dot_onas' => 'Jak se dozvěděl/a',
        'dot_prinos' => 'Přínos',
        'dot_profzivot' => 'Profesní životopis',
    ];
}

function frontend_volna_mista_application_pdf_value(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '<span class="muted">-</span>';
    }

    return nl2br(frontend_volna_mista_mail_escape($value));
}

function frontend_volna_mista_application_pdf_row(string $label, mixed $value): string
{
    return '<tr><th>' . frontend_volna_mista_mail_escape($label) . '</th><td>' . frontend_volna_mista_application_pdf_value($value) . '</td></tr>';
}

/**
 * @param array<int, array{path: string, original: string, mime: string, size: int}> $attachments
 */
function frontend_volna_mista_attachment_names(array $attachments): string
{
    $names = [];
    foreach ($attachments as $attachment) {
        $name = trim((string)($attachment['original'] ?? ''));
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return implode("\n", $names);
}

/**
 * @param array<string, mixed> $job
 * @param array<string, string> $values
 * @param array<int, array{path: string, original: string, mime: string, size: int}> $attachments
 */
function frontend_volna_mista_application_pdf_html(array $job, array $values, int $applicationId, array $attachments): string
{
    $rows = '';
    $rows .= frontend_volna_mista_application_pdf_row('Datum', (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('d.m.Y'));
    $rows .= frontend_volna_mista_application_pdf_row('Uchazeč', $values['dot_name'] ?? '');
    $rows .= frontend_volna_mista_application_pdf_row('E-mail', $values['dot_email'] ?? '');
    $rows .= frontend_volna_mista_application_pdf_row('Mobil', $values['dot_mobil'] ?? '');
    $rows .= frontend_volna_mista_application_pdf_row('Pozice', $job['title'] ?? '');
    $rows .= frontend_volna_mista_application_pdf_row('Skupina', $job['location'] ?? '');
    $rows .= frontend_volna_mista_application_pdf_row('Přílohy', frontend_volna_mista_attachment_names($attachments));

    foreach (frontend_volna_mista_application_pdf_fields() as $field => $label) {
        $rows .= frontend_volna_mista_application_pdf_row($label, $values[$field] ?? '');
    }

    return '<!doctype html><html lang="cs"><head><meta charset="utf-8"><style>
        @page { margin: 16px 20px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1f2937; font-size: 8px; line-height: 1.18; }
        h1 { margin: 0 0 2px; font-size: 14px; color: #111827; }
        .meta { margin-bottom: 7px; color: #6b7280; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { vertical-align: top; border-bottom: 0.5px solid #e5e7eb; padding: 2.5px 4px; }
        th { width: 27%; color: #374151; text-align: left; background: #f9fafb; font-weight: 700; }
        td { width: 73%; }
        .muted { color: #9ca3af; }
    </style></head><body><h1>Dotazník uchazeče #' . $applicationId . '</h1><div class="meta">Export z webu qanto.cz, vygenerováno ' . frontend_volna_mista_mail_escape((new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('d.m.Y H:i')) . '</div><table>' . $rows . '</table></body></html>';
}

/**
 * @param array<string, mixed> $job
 * @param array<string, string> $values
 * @param array<int, array{path: string, original: string, mime: string, size: int}> $attachments
 * @return array{path: string, name: string}
 */
function frontend_volna_mista_application_pdf_attachment(array $job, array $values, int $applicationId, array $attachments): array
{
    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer vendor/autoload.php neni dostupny.');
    }
    require_once $autoload;

    $options = new \Dompdf\Options();
    $options->setDefaultFont('DejaVu Sans');
    $options->setIsRemoteEnabled(false);
    $options->setIsHtml5ParserEnabled(true);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml(frontend_volna_mista_application_pdf_html($job, $values, $applicationId, $attachments), 'UTF-8');
    $dompdf->render();

    $name = strtolower(trim((string)($values['dot_name'] ?? '')));
    $name = preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: '') ?: '';
    $name = trim($name, '-');
    $filename = 'dotaznik-' . $applicationId . ($name !== '' ? '-' . $name : '') . '.pdf';
    $path = tempnam(sys_get_temp_dir(), 'qanto-dotaznik-');
    if ($path === false) {
        throw new RuntimeException('Nepodarilo se vytvorit docasny PDF soubor.');
    }
    file_put_contents($path, $dompdf->output());

    return ['path' => $path, 'name' => $filename];
}

/**
 * @param array<string, mixed> $job
 * @param array<string, string> $values
 * @param array<int, array{path: string, original: string, mime: string, size: int}> $attachments
 */
function frontend_volna_mista_application_email_html(array $job, array $values, int $applicationId, array $attachments): string
{
    $rows = [
        'ID dotazníku' => (string)$applicationId,
        'Pozice' => (string)($job['title'] ?? ''),
        'Skupina' => (string)($job['location'] ?? ''),
        'Uchazeč' => $values['dot_name'] ?? '',
        'E-mail' => $values['dot_email'] ?? '',
        'Mobil' => $values['dot_mobil'] ?? '',
        'Adresa' => $values['dot_adresa'] ?? '',
        'Datum narození' => $values['dot_birthday'] ?? '',
        'Vzdělání' => $values['dot_vzdelani'] ?? '',
        'Řidičský průkaz' => $values['dot_rp'] ?? '',
        'Předchozí zaměstnavatel' => $values['dot_predchozizam'] ?? '',
        'Funkce' => $values['dot_funkcezam'] ?? '',
        'Délka zaměstnání' => $values['dot_delkazam'] ?? '',
        'Jazykové znalosti' => $values['dot_jazyk'] ?? '',
        'Práce na PC' => $values['dot_pc'] ?? '',
        'Záliby' => $values['dot_zaliby'] ?? '',
        'Jak se o nás dozvěděl/a' => $values['dot_onas'] ?? '',
        'Přínos pro Qanto' => $values['dot_prinos'] ?? '',
        'Možná pracovní doba' => $values['dot_pracdoba'] ?? '',
        'Představa o platu' => $values['dot_plat'] ?? '',
        'Kouření' => $values['dot_koureni'] ?? '',
        'Rejstřík trestů' => $values['dot_rejstrik'] ?? '',
        'Profesní životopis' => $values['dot_profzivot'] ?? '',
        'Zdravotní stav' => $values['dot_zdravstav'] ?? '',
        'Přílohy' => frontend_volna_mista_attachment_names($attachments),
    ];

    $bodyRows = '';
    foreach ($rows as $label => $value) {
        $value = trim((string)$value);
        $bodyRows .= '<tr><th style="width:210px;padding:7px 9px;border-bottom:1px solid #e8e8e4;background:#f7f7f4;text-align:left;color:#555;font-weight:700;">' .
            frontend_volna_mista_mail_escape($label) .
            '</th><td style="padding:7px 9px;border-bottom:1px solid #e8e8e4;color:#1d1d1b;">' .
            ($value !== '' ? nl2br(frontend_volna_mista_mail_escape($value)) : '<span style="color:#999;">-</span>') .
            '</td></tr>';
    }

    return '<!doctype html><html lang="cs"><body style="margin:0;background:#f2f2ef;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#1d1d1b;">' .
        '<div style="max-width:760px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">' .
        '<div style="padding:20px 24px;background:#1d1d1b;color:#fff;"><div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#f2b7bc;">Nový kariérní dotazník</div>' .
        '<h1 style="margin:6px 0 0;font-size:24px;line-height:1.15;">' . frontend_volna_mista_mail_escape((string)($job['title'] ?? 'Volná pozice')) . '</h1></div>' .
        '<div style="padding:20px 24px;"><p style="margin:0 0 16px;color:#555;">Na webu qanto.cz byl odeslán nový dotazník uchazeče.</p>' .
        '<table style="width:100%;border-collapse:collapse;font-size:14px;">' . $bodyRows . '</table>' .
        '<p style="margin:18px 0 0;color:#777;font-size:12px;">E-mail byl vygenerován automaticky z veřejného formuláře kariéry.</p></div></div></body></html>';
}

/**
 * @param array<string, mixed> $job
 * @param array<string, string> $values
 * @param array<int, array{path: string, original: string, mime: string, size: int}> $storedAttachments
 */
function frontend_volna_mista_send_application_email(PDO $pdo, array $job, array $values, int $applicationId, array $storedAttachments): ?array
{
    $recipient = trim((string)($job['contact_email'] ?? ''));
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    if (filter_var($values['dot_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $config['smtp_reply_to'] = $values['dot_email'];
    }

    $attachments = [];
    $temporaryFiles = [];

    try {
        $pdfAttachment = frontend_volna_mista_application_pdf_attachment($job, $values, $applicationId, $storedAttachments);
        $attachments[] = $pdfAttachment;
        $temporaryFiles[] = $pdfAttachment['path'];

        foreach ($storedAttachments as $attachment) {
            $path = frontend_volna_mista_resolve_private_attachment_path((string)$attachment['path']);
            if ($path !== null) {
                $attachments[] = [
                    'path' => $path,
                    'name' => (string)$attachment['original'],
                ];
            }
        }

        $bodyHtml = frontend_volna_mista_application_email_html($job, $values, $applicationId, $storedAttachments);
        return mailer_send_smtp_logged($pdo, $config, [
            'recipient_email' => $recipient,
            'recipient_name' => (string)($job['contact_name'] ?? ''),
            'subject' => 'Nový dotazník uchazeče: ' . (string)($job['title'] ?? ''),
            'body_html' => $bodyHtml,
            'body_text' => frontend_volna_mista_application_email_text($bodyHtml),
            'attachments' => $attachments,
        ], [
            'context' => 'rep_volna_mista',
            'template_code' => 'career_application_frontend',
            'related_table' => 'rep_volna_mista_dotaznik',
            'related_id' => $applicationId,
            'payload' => [
                'job_id' => (int)($job['id'] ?? 0),
                'type_id' => (int)($job['location_id'] ?? 0),
                'has_attachment' => $storedAttachments !== [],
                'attachment_count' => count($storedAttachments),
                'has_questionnaire_pdf' => true,
            ],
        ]);
    } finally {
        foreach ($temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }
}

function frontend_volna_mista_resolve_private_attachment_path(string $storedPath): ?string
{
    $storedPath = trim($storedPath);
    if (str_starts_with($storedPath, 'private://')) {
        $relativePath = ltrim(substr($storedPath, strlen('private://')), '/');
        $root = dirname(ROOT_DIR) . '/qanto_cz_private';
    } elseif (str_starts_with($storedPath, 'protected://')) {
        $relativePath = ltrim(substr($storedPath, strlen('protected://')), '/');
        $root = frontend_volna_mista_private_storage_root();
    } else {
        return null;
    }

    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }

    $baseDir = realpath($root);
    $filePath = realpath($root . '/' . $relativePath);
    if ($baseDir === false || $filePath === false || !is_file($filePath) || !str_starts_with($filePath, $baseDir . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return $filePath;
}

/**
 * @return array{path: string, original: string, mime: string, size: int}|null
 */
function frontend_volna_mista_store_application_attachment(?array $file, int $applicationId, int $order = 1): ?array
{
    if ($file === null || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(ui_text('kariera.application.error_upload', 'Přílohu se nepodařilo nahrát.'));
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException(ui_text('kariera.application.error_upload', 'Přílohu se nepodařilo nahrát.'));
    }

    $size = (int)($file['size'] ?? 0);
    $maxSize = 10 * 1024 * 1024;
    if ($size <= 0 || $size > $maxSize) {
        throw new RuntimeException(ui_text('kariera.application.error_upload_size', 'Příloha může mít maximálně 10 MB.'));
    }

    $original = trim((string)($file['name'] ?? 'priloha'));
    $extension = strtolower((string)pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException(ui_text('kariera.application.error_upload_type', 'Příloha musí být PDF, DOC nebo DOCX.'));
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)($finfo->file($tmpName) ?: 'application/octet-stream');
    $allowedMimes = [
        'pdf' => ['application/pdf', 'application/octet-stream'],
        'doc' => ['application/msword', 'application/x-msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];
    if (!in_array($mime, $allowedMimes[$extension], true)) {
        throw new RuntimeException(ui_text('kariera.application.error_upload_type', 'Příloha musí být PDF, DOC nebo DOCX.'));
    }

    $relativeDir = 'volna-mista/dotazniky';
    $absoluteDir = frontend_volna_mista_private_storage_root() . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException(ui_text('kariera.application.error_upload', 'Přílohu se nepodařilo nahrát.'));
    }

    $base = frontend_volna_mista_upload_slug(pathinfo($original, PATHINFO_FILENAME), 'priloha');
    $relativePath = $relativeDir . '/dotaznik-' . $applicationId . '-' . str_pad((string)$order, 2, '0', STR_PAD_LEFT) . '-' . $base . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
    $absolutePath = frontend_volna_mista_private_storage_root() . '/' . $relativePath;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new RuntimeException(ui_text('kariera.application.error_upload', 'Přílohu se nepodařilo nahrát.'));
    }

    return [
        'path' => 'protected://' . $relativePath,
        'original' => mb_substr($original !== '' ? $original : basename($relativePath), 0, 255, 'UTF-8'),
        'mime' => mb_substr($mime, 0, 100, 'UTF-8'),
        'size' => $size,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_volna_mista_normalize_uploaded_attachments(?array $fileInput): array
{
    if ($fileInput === null) {
        return [];
    }

    $names = $fileInput['name'] ?? null;
    if (!is_array($names)) {
        return [(array)$fileInput];
    }

    $files = [];
    foreach ($names as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $fileInput['type'][$index] ?? '',
            'tmp_name' => $fileInput['tmp_name'][$index] ?? '',
            'error' => $fileInput['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileInput['size'][$index] ?? 0,
        ];
    }

    return $files;
}

/**
 * @return array<int, array{path: string, original: string, mime: string, size: int}>
 */
function frontend_volna_mista_store_application_attachments(?array $fileInput, int $applicationId): array
{
    $files = array_filter(
        frontend_volna_mista_normalize_uploaded_attachments($fileInput),
        static fn(array $file): bool => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    );

    if ($files === []) {
        return [];
    }

    $maxFiles = 5;
    if (count($files) > $maxFiles) {
        throw new RuntimeException(ui_text('kariera.application.error_upload_count', 'Nahrát lze maximálně 5 souborů.'));
    }

    $stored = [];
    $order = 1;
    try {
        foreach ($files as $file) {
            $attachment = frontend_volna_mista_store_application_attachment($file, $applicationId, $order);
            if ($attachment !== null) {
                $stored[] = $attachment;
                $order++;
            }
        }
    } catch (Throwable $e) {
        foreach ($stored as $attachment) {
            $path = frontend_volna_mista_resolve_private_attachment_path((string)$attachment['path']);
            if ($path !== null && is_file($path)) {
                @unlink($path);
            }
        }
        throw $e;
    }

    return $stored;
}

/**
 * @param array{path: string, original: string, mime: string, size: int} $attachment
 */
function frontend_volna_mista_insert_application_attachment(PDO $pdo, int $applicationId, array $attachment, int $order): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO rep_volna_mista_dotaznik_prilohy
         (dotaznik_id, file_path, original_name, mime_type, file_size, poradi, valid, user_i, user_u)
         VALUES (:dotaznik_id, :file_path, :original_name, :mime_type, :file_size, :poradi, 1, :user_i, :user_u)'
    );
    $stmt->execute([
        ':dotaznik_id' => $applicationId,
        ':file_path' => $attachment['path'],
        ':original_name' => $attachment['original'],
        ':mime_type' => $attachment['mime'],
        ':file_size' => $attachment['size'],
        ':poradi' => $order,
        ':user_i' => 'frontend',
        ':user_u' => 'frontend',
    ]);
}

/**
 * @return array{ok: bool, message: string, values: array<string, string>, mail_sent: bool}
 */
function frontend_volna_mista_application_submit(array $post, array $files, string $lang = 'cz'): array
{
    global $pdo;

    $fields = [
        'dot_name', 'dot_adresa', 'dot_birthday', 'dot_mobil', 'dot_email',
        'dot_predchozizam', 'dot_delkazam', 'dot_funkcezam', 'dot_vzdelani', 'dot_rp',
        'dot_jazyk', 'dot_pc', 'dot_zaliby', 'dot_onas', 'dot_prinos',
        'dot_pracdoba', 'dot_plat', 'dot_koureni', 'dot_rejstrik', 'dot_profzivot',
        'dot_zdravstav',
    ];

    $values = [];
    foreach ($fields as $field) {
        $values[$field] = frontend_volna_mista_clean_post_value($post[$field] ?? '', 6000);
    }

    try {
        if (!($pdo instanceof PDO)) {
            throw new RuntimeException(ui_text('kariera.application.error_generic', 'Dotazník se nepodařilo odeslat. Zkuste to prosím znovu.'));
        }

        if (function_exists('frontend_captcha_validate')) {
            $captcha = frontend_captcha_validate('career_application', $post);
            if (!empty($captcha['bot'])) {
                return [
                    'ok' => true,
                    'message' => ui_text('kariera.application.success', 'Dotazník byl odeslán. Děkujeme.'),
                    'values' => [],
                    'mail_sent' => false,
                ];
            }
            if (empty($captcha['ok'])) {
                throw new RuntimeException((string)$captcha['message']);
            }
        }

        $csrf = (string)($post['csrf_token'] ?? '');
        $expectedCsrf = frontend_volna_mista_application_csrf_token();
        if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
            throw new RuntimeException(ui_text('kariera.application.error_security', 'Formulář vypršel, obnovte stránku a zkuste odeslání znovu.'));
        }

        $jobId = max(0, (int)($post['volne_misto_id'] ?? 0));
        $job = frontend_volna_mista_job_detail($jobId, $lang);
        if ($job === null) {
            throw new RuntimeException(ui_text('kariera.application.error_position', 'Vybraná pozice není dostupná.'));
        }

        if ($values['dot_name'] === '' || $values['dot_mobil'] === '' || $values['dot_email'] === '') {
            throw new RuntimeException(ui_text('kariera.application.error_required', 'Vyplňte prosím jméno, mobil a e-mail.'));
        }
        if (!filter_var($values['dot_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(ui_text('kariera.application.error_email', 'Zadejte prosím platný e-mail.'));
        }

        $insertValues = [
            ':dot_datum' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('Y-m-d'),
            ':volne_misto_id' => (int)$job['id'],
            ':typ_id' => (int)$job['location_id'],
            ':dot_pozice' => mb_substr((string)$job['title'], 0, 256, 'UTF-8'),
            ':dot_name' => mb_substr($values['dot_name'], 0, 256, 'UTF-8'),
            ':dot_adresa' => $values['dot_adresa'],
            ':dot_birthday' => $values['dot_birthday'],
            ':dot_mobil' => $values['dot_mobil'],
            ':dot_email' => $values['dot_email'],
            ':dot_predchozizam' => $values['dot_predchozizam'],
            ':dot_delkazam' => $values['dot_delkazam'],
            ':dot_funkcezam' => $values['dot_funkcezam'],
            ':dot_vzdelani' => $values['dot_vzdelani'],
            ':dot_rp' => $values['dot_rp'],
            ':dot_jazyk' => $values['dot_jazyk'],
            ':dot_pc' => $values['dot_pc'],
            ':dot_zaliby' => $values['dot_zaliby'],
            ':dot_onas' => $values['dot_onas'],
            ':dot_prinos' => $values['dot_prinos'],
            ':dot_pracdoba' => $values['dot_pracdoba'],
            ':dot_plat' => $values['dot_plat'],
            ':dot_koureni' => mb_substr($values['dot_koureni'], 0, 10, 'UTF-8'),
            ':dot_rejstrik' => $values['dot_rejstrik'],
            ':dot_profzivot' => $values['dot_profzivot'],
            ':dot_zdravstav' => mb_substr($values['dot_zdravstav'], 0, 256, 'UTF-8'),
            ':user_i' => 'frontend',
            ':user_u' => 'frontend',
        ];

        $storedAttachments = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO rep_volna_mista_dotaznik
                 (dot_datum, volne_misto_id, typ_id, dot_pozice, dot_name, dot_adresa, dot_birthday, dot_mobil, dot_email,
                  dot_predchozizam, dot_delkazam, dot_funkcezam, dot_vzdelani, dot_rp, dot_jazyk, dot_pc, dot_zaliby, dot_onas,
                  dot_prinos, dot_pracdoba, dot_plat, dot_koureni, dot_rejstrik, dot_profzivot, dot_zdravstav, valid, user_i, user_u)
                 VALUES
                 (:dot_datum, :volne_misto_id, :typ_id, :dot_pozice, :dot_name, :dot_adresa, :dot_birthday, :dot_mobil, :dot_email,
                  :dot_predchozizam, :dot_delkazam, :dot_funkcezam, :dot_vzdelani, :dot_rp, :dot_jazyk, :dot_pc, :dot_zaliby, :dot_onas,
                  :dot_prinos, :dot_pracdoba, :dot_plat, :dot_koureni, :dot_rejstrik, :dot_profzivot, :dot_zdravstav, 1, :user_i, :user_u)'
            );
            $stmt->execute($insertValues);
            $applicationId = (int)$pdo->lastInsertId();

            $storedAttachments = frontend_volna_mista_store_application_attachments($files['dot_priloha'] ?? null, $applicationId);
            if ($storedAttachments !== []) {
                $primaryAttachment = $storedAttachments[0];
                $stmt = $pdo->prepare(
                    'UPDATE rep_volna_mista_dotaznik
                     SET dot_priloha_file = :file, dot_priloha_original = :original, dot_priloha_mime = :mime, dot_priloha_size = :size
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':file' => $primaryAttachment['path'],
                    ':original' => $primaryAttachment['original'],
                    ':mime' => $primaryAttachment['mime'],
                    ':size' => $primaryAttachment['size'],
                    ':id' => $applicationId,
                ]);

                foreach ($storedAttachments as $index => $attachment) {
                    frontend_volna_mista_insert_application_attachment($pdo, $applicationId, $attachment, $index + 1);
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($storedAttachments as $storedAttachment) {
                $storedAttachmentPath = frontend_volna_mista_resolve_private_attachment_path((string)$storedAttachment['path']);
                if ($storedAttachmentPath !== null && is_file($storedAttachmentPath)) {
                    @unlink($storedAttachmentPath);
                }
            }
            throw $e;
        }

        $mailSent = false;
        try {
            $mailResult = frontend_volna_mista_send_application_email($pdo, $job, $values, $applicationId, $storedAttachments);
            $mailSent = is_array($mailResult);
        } catch (Throwable) {
            $mailSent = false;
        }

        return [
            'ok' => true,
            'message' => ui_text('kariera.application.success', 'Dotazník byl odeslán. Děkujeme.'),
            'values' => [],
            'mail_sent' => $mailSent,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => $e->getMessage(),
            'values' => $values,
            'mail_sent' => false,
        ];
    }
}
